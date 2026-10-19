<?php
declare(strict_types=1);

/**
 * MaplePass x 独角数卡 (DuojiaoFaka) 官方全自动发卡/代充 Webhook 接收端
 * 
 * 当独角数卡完成买家支付后，调用本接口，本接口将：
 * 1. 严格校验请求合法性与签名密钥
 * 2. 映射商品对应关系 (goods_id -> MaplePass 产品代码)
 * 3. 实时调用 MaplePass API 下单采购 (毫秒级出卡，严格幂等防重复扣款)
 * 4. 组装卡密信息并返回；若配置了数据库连接，可直接自动回写独角数卡订单状态
 * 
 * 文档与支持：https://maple1189ai.com/guide/faka-api-integration
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/MaplePassClient.php';

use MaplePass\MaplePassClient;

// 日志记录助手
function writeLog(string $message, array $context = []): void {
    $configFile = file_exists(__DIR__ . '/../config.php') ? __DIR__ . '/../config.php' : __DIR__ . '/../config.sample.php';
    $config = require $configFile;
    if (empty($config['debug'])) return;

    $logFile = $config['log_path'] ?? (__DIR__ . '/../logs/maplepass.log');
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $time = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    @file_put_contents($logFile, "[{$time}] {$message}{$contextStr}\n", FILE_APPEND);
}

try {
    // 1. 读取配置文件
    $configFile = file_exists(__DIR__ . '/../config.php') ? __DIR__ . '/../config.php' : __DIR__ . '/../config.sample.php';
    if (!file_exists($configFile)) {
        throw new Exception("配置文件不存在，请先配置 config.php");
    }
    $config = require $configFile;

    // 2. 解析请求参数 (支持 application/json 及 application/x-www-form-urlencoded)
    $rawInput = file_get_contents('php://input');
    $data = [];
    if (!empty($rawInput)) {
        $json = json_decode($rawInput, true);
        if (is_array($json)) {
            $data = $json;
        }
    }
    if (empty($data)) {
        $data = array_merge($_GET, $_POST);
    }

    writeLog("收到 Webhook 发卡请求", ['ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'params' => $data]);

    // 3. 安全鉴权校验 (Token / Secret)
    $reqSecret = $data['token'] ?? ($data['secret'] ?? ($_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? ''));
    if (!empty($config['webhook_secret']) && $config['webhook_secret'] !== 'your_custom_webhook_secret_key_here') {
        if (empty($reqSecret) || !hash_equals((string)$config['webhook_secret'], (string)$reqSecret)) {
            http_response_code(403);
            echo json_encode(['code' => 403, 'message' => '鉴权失败：Webhook Secret 不匹配']);
            writeLog("鉴权失败: Secret 不匹配");
            exit;
        }
    }

    // 4. 参数提取与校验
    $orderSn = trim((string)($data['order_sn'] ?? ($data['order_id'] ?? ($data['trade_no'] ?? ''))));
    $goodsId = $data['goods_id'] ?? ($data['product_id'] ?? null);
    $quantity = max(1, intval($data['quantity'] ?? ($data['buy_amount'] ?? 1)));

    if (empty($orderSn)) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '缺少必填参数 order_sn']);
        exit;
    }

    // 5. 查找商品映射
    $productMap = $config['product_map'] ?? [];
    $mapleProduct = null;

    if (isset($productMap[$goodsId])) {
        $mapleProduct = $productMap[$goodsId];
    } elseif (isset($data['product_key']) && is_string($data['product_key'])) {
        $mapleProduct = trim($data['product_key']);
    }

    if (empty($mapleProduct)) {
        throw new Exception("未找到商品 ID [{$goodsId}] 对应的 MaplePass 货源映射，请检查 config.php 中的 product_map 配置。");
    }

    // 6. 调用 MaplePass 官方客户端发起采购
    $client = new MaplePassClient($config['api_key'], $config['base_url'] ?? 'https://maple1189ai.com', $config['timeout'] ?? 25);

    // 构造标准的幂等键 (DuojiaoFaka 订单号 + 前缀，确保 8~80 字符且绝不重复扣费)
    $idempotencyKey = 'faka_' . substr(preg_replace('/[^A-Za-z0-9._:-]/', '', $orderSn), 0, 70);
    if (strlen($idempotencyKey) < 8) {
        $idempotencyKey = 'faka_sn_' . $orderSn;
    }

    writeLog("正在向 MaplePass 下单", [
        'order_sn' => $orderSn,
        'product' => $mapleProduct,
        'quantity' => $quantity,
        'idempotency_key' => $idempotencyKey
    ]);

    $orderResult = $client->createOrder($mapleProduct, $quantity, $idempotencyKey);

    // 7. 解析返回卡密信息
    $deliveries = $orderResult['deliveries'] ?? [];
    if (empty($deliveries) && !empty($orderResult['card_code'])) {
        $deliveries = [['card_code' => $orderResult['card_code'], 'redeem_url' => $orderResult['redeem_url'] ?? '']];
    }

    $cardLines = [];
    $template = $config['delivery_template'] ?? "{card_code}";

    foreach ($deliveries as $item) {
        $cardCode = $item['card_code'] ?? ($item['code'] ?? '');
        $redeemUrl = $item['redeem_url'] ?? ($item['url'] ?? 'https://maple1189ai.com/redeem');
        $title = $orderResult['product_title'] ?? $mapleProduct;

        $formatted = str_replace(
            ['{product_title}', '{card_code}', '{redeem_url}'],
            [$title, $cardCode, $redeemUrl],
            $template
        );
        $cardLines[] = $formatted;
    }

    $finalCardInfo = implode("\n---------------------------\n", $cardLines);

    writeLog("采购成功，出卡完成", [
        'order_sn' => $orderSn,
        'maple_order_id' => $orderResult['order_id'] ?? '',
        'cards_count' => count($deliveries)
    ]);

    // 8. 返回成功响应
    echo json_encode([
        'code' => 200,
        'status' => 'success',
        'message' => '发卡成功',
        'data' => [
            'order_sn' => $orderSn,
            'maple_order_id' => $orderResult['order_id'] ?? null,
            'card_info' => $finalCardInfo,
            'deliveries' => $deliveries,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    writeLog("发卡异常: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
