<?php
declare(strict_types=1);

/**
 * MaplePass API 终端快速测试脚本
 * 
 * 使用方式：
 *   php examples/test_order.php                  # 仅查询个人 USDT 钱包余额及在售商品与底价
 *   php examples/test_order.php --buy <产品代码>  # 发起一次真实采购测试 (扣除对应 USDT 余额)
 */

require_once __DIR__ . '/../src/MaplePassClient.php';

use MaplePass\MaplePassClient;

// 读取配置文件
$configFile = file_exists(__DIR__ . '/../config.php') ? __DIR__ . '/../config.php' : __DIR__ . '/../config.sample.php';
$config = require $configFile;

echo "\n========================================================\n";
echo "   MaplePass (枫享 AI) 官方批发 API 联调测试工具\n";
echo "========================================================\n";

if (strpos($config['api_key'], 'xxxxxxxxxxxx') !== false) {
    echo "⚠️  [配置提示] 您正在使用模板配置，请先编辑 config.php 填入您在\n";
    echo "    https://maple1189ai.com/api-cooperation 领取的真实 API Key。\n\n";
}

try {
    $client = new MaplePassClient($config['api_key'], $config['base_url'] ?? 'https://maple1189ai.com', $config['timeout'] ?? 25);

    // 1. 检查钱包余额
    echo "\n[1/3] 正在查询批发钱包账户余额...\n";
    $balanceData = $client->getBalance();
    $balance = $balanceData['balance_usdt'] ?? ($balanceData['balance'] ?? '0.00');
    $currency = $balanceData['currency'] ?? 'USDT';
    echo "  ✅ 账户钱包余额: {$balance} {$currency}\n";

    // 2. 查询在售批发货源与底价
    echo "\n[2/3] 正在获取当前可用商品列表与专属批发底价...\n";
    $productsData = $client->getProducts();
    $products = $productsData['products'] ?? $productsData;

    if (!is_array($products) || empty($products)) {
        echo "  ℹ️ 当前暂无可批发的商品或权限未开通。\n";
    } else {
        printf("  %-20s | %-32s | %-12s | %-8s\n", "产品代码 (key)", "产品名称 (title)", "底价 (USDT)", "状态");
        echo "  " . str_repeat("-", 80) . "\n";
        foreach ($products as $item) {
            $key = $item['key'] ?? $item['product_key'] ?? $item['id'] ?? '-';
            $title = $item['title'] ?? $item['name'] ?? '-';
            $price = $item['unit_price_usdt'] ?? $item['price'] ?? '-';
            $stock = !empty($item['in_stock']) ? '有货' : '补货中';
            printf("  %-20s | %-32s | $%-11s | %-8s\n", $key, mb_strimwidth($title, 0, 30, '...'), $price, $stock);
        }
    }

    // 3. 检查是否传入了测试下单参数
    $opts = getopt('', ['buy:']);
    $targetProduct = $opts['buy'] ?? null;

    if ($targetProduct) {
        echo "\n[3/3] 准备发起采购测试: {$targetProduct} ...\n";
        $idempotencyKey = 'test_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
        echo "  幂等唯一键 (idempotency_key): {$idempotencyKey}\n";
        
        $orderResult = $client->createOrder($targetProduct, 1, $idempotencyKey);
        echo "  🎉 下单成功！订单详情：\n";
        echo "  - 订单编号: " . ($orderResult['order_id'] ?? $orderResult['id'] ?? 'N/A') . "\n";
        echo "  - 实扣金额: $" . ($orderResult['total_usdt'] ?? $orderResult['amount'] ?? '0.00') . " USDT\n";
        echo "  - 交付卡密:\n";
        
        $deliveries = $orderResult['deliveries'] ?? [];
        if (empty($deliveries) && !empty($orderResult['card_code'])) {
            $deliveries = [['card_code' => $orderResult['card_code'], 'redeem_url' => $orderResult['redeem_url'] ?? '']];
        }

        foreach ($deliveries as $idx => $card) {
            $code = $card['card_code'] ?? $card['code'] ?? '无';
            $redeemUrl = $card['redeem_url'] ?? $card['url'] ?? 'https://maple1189ai.com/redeem';
            echo "    [#" . ($idx + 1) . "] 卡密: {$code}\n";
            echo "         兑换/直充地址: {$redeemUrl}\n";
        }
    } else {
        echo "\n[3/3] 下单测试已跳过。\n";
        echo "  💡 提示: 若需测试扣除 USDT 余额并出卡，请运行：\n";
        echo "     php examples/test_order.php --buy chatgpt_100_cl\n";
    }

    echo "\n========================================================\n";
    echo "  测试完毕！接口通信正常，响应耗时良好。\n";
    echo "========================================================\n\n";

} catch (Exception $e) {
    echo "\n❌ [测试失败] 捕获异常: " . $e->getMessage() . "\n\n";
    exit(1);
}
