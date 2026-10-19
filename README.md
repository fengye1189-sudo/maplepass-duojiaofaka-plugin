# MaplePass 独角数卡 (DuojiaoFaka) 全自动发卡/代充插件

<p align="center">
  <a href="https://maple1189ai.com/api-cooperation"><img src="https://img.shields.io/badge/MaplePass-Wholesale%20API-blue.svg" alt="MaplePass API"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-%3E%3D7.4%20%7C%208.x-8892BF.svg" alt="PHP Version"></a>
  <a href="https://github.com/assimon/duojiaoka"><img src="https://img.shields.io/badge/DuojiaoFaka-2.x%20Compatible-brightgreen.svg" alt="DuojiaoFaka"></a>
  <a href="#"><img src="https://img.shields.io/badge/Settlement-100%25%20USDT-orange.svg" alt="USDT Settlement"></a>
  <a href="./LICENSE"><img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License"></a>
</p>

---

## 📖 项目简介

**MaplePass 独角数卡插件** 是专为 **发卡网站长（独角数卡、异世界发卡、各类发卡商城）** 及独立电商打造的自动化货源对接工具。

通过本插件，您可以将您的独角数卡与 [MaplePass (枫享 AI) 官方批发开放平台](https://maple1189ai.com/api-cooperation) 秒级打通，实现 **买家付款 -> 自动调用 API 采购 -> 毫秒级生成兑换码/直充链接 -> 自动发货到买家屏幕与邮箱** 的 100% 全自动闭环！

---

## 🌟 为什么发卡网站长选择 MaplePass 货源？

### 1. ⚡ 零囤货 · 零资金积压
无需提前采购成百上千张卡密囤积在数据库。采用实时按需调取机制：买家在您的发卡网完成付款，系统才实时扣减 USDT 余额出卡，资金周转率最大化。

### 2. 🛡️ 100% 极简独立白标（绝对防撬单设计）
我们深知发卡网与分销商最关心的是**私域客户防流失**：
- 交付给终端买家的兑换站 (`/redeem`) 采用**完全独立的纯净白标模式**。
- 兑换页面彻底隐藏了全站顶部导航、商城链接、卡密购买入口等所有导流元素。
- 买家在兑换页面只会看到纯净的激活/直充流程，**绝不会发现您的供货源头，彻底杜绝撬单**！

### 3. 💰 行业顶级利润空间（纯 USDT 链上结算）
无需繁琐的国内企业资质或敏感审核，支持 USDT 自动充值入账，全自动化结算：

| 权益产品 | 官网原价 | 专属批发底价 (USDT) | 建议零售价 | 发卡网利润 |
| :--- | :--- | :--- | :--- | :--- |
| **ChatGPT 200 美元档**<br>*(菲律宾卡充 / Pro 5倍旗舰)* | $200.00 | **$150.00** | $175 ~ $190 | **净赚 $25 ~ $40 / 单** |
| **ChatGPT 100 美元档**<br>*(智利特惠专线)* | $100.00 | **$93.00** | $97 ~ $99 | **走量爆款 · 高复购率** |
| **ChatGPT 100 美元档**<br>*(菲律宾卡充)* | $100.00 | **$95.00** | $98 ~ $99 | **极速秒充 · 成功率极高** |
| **Gemini 会员**<br>*(18个月全功能 + 5TB 网盘)* | $450.00 | **$3.00** | $10 ~ $18 | **翻倍暴利 · 转化神器** |
| **ChatGPT Plus** *(1个月官方充值/卡密)* | $20.00 | **$16.70** | $18.5 ~ $20 | 刚需走量 · 极速秒充 |

> 💡 **原号保留优势**：所有充值产品均支持终端买家使用个人主力老号原号覆盖升级，历史对话、自定义 GPTs、Memory 记忆库 100% 完整保留，买家无需提供账号密码。

### 4. 🔒 30 天官方全额质保 & 幂等防重扣
- **HMAC-SHA256 签名机制**：接口采用金融级签名与时间戳防重放。
- **强制 Idempotency 幂等保护**：网络超时或重试不会导致二次重复扣费；接口未成功出码 **0 扣费**。
- **正规官方质保**：若遇 OpenAI / Google 官方不可抗力政策变动，支持秒级自动补发或按天比例退款。
- **钱包余额随时提现**：账户内未消耗的 USDT 支持随时在后台申请原路退款提现。

---

## 🚀 极速安装与配置（3 分钟快速搞定）

### 第一步：获取批发 API Key 与充值
1. 访问 [MaplePass 批发开放平台申请页面](https://maple1189ai.com/api-cooperation)。
2. 提交您的发卡网或店铺信息（审核极速通过）。
3. 审核通过后，进入控制台生成并复制您的专属 `API Key`（格式如：`mp_live_xxxxxxxxxxxx_xxxxxxxxxxxxxxxxxxxxxxxxxxx`）。
4. 在控制台充值 USDT 余额（支持 TRC20 / BEP20 等，自动到账）。

---

### 第二步：部署插件文件
将本项目克隆或上传至您的独角数卡服务器中（例如放入 `/www/wwwroot/duojiaoka/public/plugins/maplepass` 或单独目录）：

```bash
cd /www/wwwroot/duojiaoka/public/plugins
git clone https://github.com/your-username/maplepass-duojiaofaka-plugin.git maplepass
cd maplepass
```

复制配置文件模版并修改：
```bash
cp config.sample.php config.php
nano config.php # 或使用宝塔/VS Code 编辑
```

在 `config.php` 中配置：
```php
return [
    // 填入您在 MaplePass 控制台获取的 API Key
    'api_key' => 'mp_live_xxxxxxxxxxxx_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'base_url' => 'https://maple1189ai.com',

    // 自定义 Webhook 安全密钥 (防止未经授权的请求)
    'webhook_secret' => 'your_custom_secret_password_here',

    // 商品映射表：独角数卡后台商品ID => MaplePass 产品代码
    'product_map' => [
        1 => 'chatgpt_pro20_ph', // 独角数卡ID为1的商品 对应 ChatGPT 200 Pro
        2 => 'chatgpt_100_cl',    // 独角数卡ID为2的商品 对应 ChatGPT 100 智利
        3 => 'chatgpt_100_ph',    // 独角数卡ID为3的商品 对应 ChatGPT 100 菲律宾
        4 => 'gemini_pro_18m',    // 独角数卡ID为4的商品 对应 Gemini 18个月+5TB
    ],
    
    'debug' => true,
];
```

---

### 第三步：在独角数卡后台配置自动发货

#### 方式 A：外部 Webhook 异步回调模式（推荐）
在独角数卡或您的支付回调事件中，将请求 POST 至：
`https://您的发卡域名.com/plugins/maplepass/examples/duojiaofaka_webhook.php`

**请求参数传递：**
```json
{
  "order_sn": "202610190001",
  "goods_id": 1,
  "quantity": 1,
  "token": "your_custom_secret_password_here"
}
```

插件将自动返回标准 JSON 并附带格式化卡密与兑换链接：
```json
{
  "code": 200,
  "status": "success",
  "message": "发卡成功",
  "data": {
    "order_sn": "202610190001",
    "maple_order_id": "ord_9f81a7b6c5d4",
    "card_info": "【MaplePass 自动交付成功】\n产品名称: ChatGPT 200\n交付内容: MP-PRO-8899-AAAA\n直充/兑换地址: https://maple1189ai.com/redeem?token=xxx\n...",
    "deliveries": [
      {
        "card_code": "MP-PRO-8899-AAAA",
        "redeem_url": "https://maple1189ai.com/redeem?token=xxx"
      }
    ]
  }
}
```

#### 方式 B：PHP 原生 SDK 方式集成
若您二次开发了独角数卡的控制器或事件监听器（如 `App\Jobs\OrderDelivery`）：
```php
use MaplePass\MaplePassClient;

$client = new MaplePassClient('mp_live_xxxxxxxxxxxx_xxxxxxxxxxxxxxxxxxxxxxxxxxx');

// 一行代码全自动扣费并出卡
$res = $client->createOrder('chatgpt_100_cl', 1, 'order_' . $order->order_sn);

// 获取交付卡密
$cardCode = $res['deliveries'][0]['card_code'];
$redeemUrl = $res['deliveries'][0]['redeem_url'];

// 将卡密写入独角数卡订单详情
$order->info = "卡密：{$cardCode}\n兑换地址：{$redeemUrl}";
$order->status = \App\Models\Order::STATUS_COMPLETED;
$order->save();
```

---

## 🛠️ 命令行快速自检与联调工具

插件内置了极简的命令行联调工具，您可以在终端一键验证接口连通性、查余额及测试下单：

### 1. 查询钱包 USDT 余额与实时货源底价
```bash
php examples/test_order.php
```

输出示例：
```text
========================================================
   MaplePass (枫享 AI) 官方批发 API 联调测试工具
========================================================

[1/3] 正在查询批发钱包账户余额...
  ✅ 账户钱包余额: 500.00 USDT

[2/3] 正在获取当前可用商品列表与专属批发底价...
  产品代码 (key)        | 产品名称 (title)                 | 底价 (USDT)   | 状态    
  --------------------------------------------------------------------------------
  chatgpt_pro20_ph     | ChatGPT 200 Pro (菲律宾5倍旗舰)   | $150.00       | 有货    
  chatgpt_100_cl       | ChatGPT 100 美元 (智利特惠专线)  | $93.00        | 有货    
  chatgpt_100_ph       | ChatGPT 100 美元 (菲律宾卡充)    | $95.00        | 有货    
  gemini_pro_18m       | Gemini 18个月+5TB 网盘           | $3.00         | 有货    
```

### 2. 发起一次真实采购测试
```bash
php examples/test_order.php --buy chatgpt_100_cl
```

---

## ❓ 常见问题 FAQ

### Q1: 充值之后扣款是如何计算的？
A: 您的 MaplePass 账户内有一个统一的 **USDT 现货钱包**。每次调用接口下单出卡时，系统会实时扣除对应的批发底价。若接口因为库存不足、网络超时等原因未成功返回卡密，**系统绝对零扣费**。

### Q2: 买家收到卡密后如何操作？
A: 买家直接打开交付信息中的 `https://maple1189ai.com/redeem` 兑换页面，输入卡密与他们的 ChatGPT / Google 账号即可一键覆盖生效。兑换页面无任何 MaplePass 主站购买入口或标价，全白标展示。

### Q3: 遇到封号或掉期如何处理？
A: 平台所有产品均提供 **30 天正规通道质保**。在 MaplePass 控制台提交工单，技术客服 1 小时内响应，若为不可抗力支持直接补号或按天折算退款至 USDT 余额。

### Q4: 如何联系商务与技术支持？
- 官方开放平台：[https://maple1189ai.com/api-cooperation](https://maple1189ai.com/api-cooperation)
- 纸飞机技术支持：Telegram: [@fengye1189](https://t.me/fengye1189)
- 开发者对接教程：[发卡网全自动 API 对接指南](https://maple1189ai.com/guide/faka-api-integration)

---

## 📄 开源许可证

本项目基于 [MIT License](./LICENSE) 协议开源。
