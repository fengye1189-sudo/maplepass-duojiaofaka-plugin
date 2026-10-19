<?php
declare(strict_types=1);

namespace MaplePass;

use Exception;

/**
 * MaplePass (枫享 AI) 官方批发 API PHP 客户端
 * 适配独角数卡 (DuojiaoFaka)、异世界发卡及各类独立电商系统
 * 
 * 官网与控制台: https://maple1189ai.com/api-cooperation
 * 技术接入指南: https://maple1189ai.com/guide/faka-api-integration
 */
class MaplePassClient
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    /**
     * @param string $apiKey 申请审核通过后获取的 API Key (格式: mp_live_xxxxxxxxxxxx_xxxxxxxxxxxxxxxxxxxxxxxxxxx)
     * @param string $baseUrl 接口根域名
     * @param int $timeout 网络请求超时时间（秒）
     */
    public function __construct(string $apiKey, string $baseUrl = 'https://maple1189ai.com', int $timeout = 25)
    {
        $this->apiKey = trim($apiKey);
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;

        if (empty($this->apiKey) || !preg_match('/^mp_live_[a-f0-9]{12}_[A-Za-z0-9_-]{43}$/', $this->apiKey)) {
            throw new Exception("MaplePass API Key 格式无效，请检查后台生成的密钥。");
        }
    }

    /**
     * 查询个人批发钱包 USDT 余额
     * @return array ['balance_usdt' => '100.00', 'currency' => 'USDT', ...]
     */
    public function getBalance(): array
    {
        return $this->request('GET', '/api/v1/wholesale/balance');
    }

    /**
     * 查询当前可批发的商品列表、批发底价与库存状态
     * @return array 包含商品 key、名称、批发单价 unit_price_usdt、库存状态 in_stock 等
     */
    public function getProducts(): array
    {
        return $this->request('GET', '/api/v1/wholesale/products');
    }

    /**
     * 下单采购卡密或激活直充链接（全自动秒级出卡）
     * 
     * @param string $product 商品标识 (如: chatgpt_pro20_ph, chatgpt_plus_1m, gemini_pro_18m 等)
     * @param int $quantity 采购数量 (通常为 1)
     * @param string $idempotencyKey 幂等唯一键 (例如: faka_order_20261019001，防止网络抖动导致重复扣款)
     * @return array 返回订单详情及交付卡密 deliveries 数组
     */
    public function createOrder(string $product, int $quantity, string $idempotencyKey): array
    {
        $payload = [
            'product' => trim($product),
            'quantity' => $quantity,
            'idempotency_key' => trim($idempotencyKey),
        ];

        return $this->request('POST', '/api/v1/wholesale/orders', $payload);
    }

    /**
     * 查询指定订单的交付详情与卡密
     * @param string $orderId 订单 ID
     */
    public function getOrder(string $orderId): array
    {
        return $this->request('GET', '/api/v1/wholesale/orders/' . rawurlencode(trim($orderId)));
    }

    /**
     * 底层安全请求方法（自动计算 HMAC-SHA256 签名）
     */
    private function request(string $method, string $path, array $data = []): array
    {
        $url = $this->baseUrl . $path;
        $timestamp = strval(time());
        $method = strtoupper($method);
        $rawBody = '';

        if ($method === 'POST') {
            $rawBody = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($rawBody === false) {
                throw new Exception("请求数据 JSON 序列化失败。");
            }
        }

        // 计算标准的规范化待签名字符串: timestamp \n METHOD \n PATH \n SHA256(body)
        $bodyHash = hash('sha256', $rawBody);
        $canonical = "{$timestamp}\n{$method}\n{$path}\n{$bodyHash}";
        $signature = hash_hmac('sha256', $canonical, $this->apiKey);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'X-Maple-Timestamp: ' . $timestamp,
            'X-Maple-Signature: ' . $signature,
            'Accept: application/json',
        ];

        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/json';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
        }

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            throw new Exception("连接 MaplePass API 失败: " . $curlError);
        }

        $result = json_decode((string)$responseBody, true);
        if (!is_array($result)) {
            throw new Exception("MaplePass API 返回格式非有效 JSON (HTTP {$httpCode}): " . $responseBody);
        }

        if ($httpCode >= 400) {
            $errorCode = $result['error'] ?? 'API_ERROR';
            $errorMessage = $result['message'] ?? '请求处理失败';
            throw new Exception("MaplePass 接口报错 [{$errorCode}]: {$errorMessage}", $httpCode);
        }

        return $result;
    }
}
