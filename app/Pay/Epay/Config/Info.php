<?php
declare(strict_types=1);

use App\Consts\Pay;

return [
    'name'    => '易支付',
    'version' => '1.0.0',
    'author'  => 'System Built-in',
    'desc'    => '彩虹易支付聚合支付接口，支持支付宝/微信等通道。',
    'options' => [
        'alipay' => '支付宝',
        'wxpay'  => '微信支付',
        'qqpay'  => 'QQ钱包',
    ],
    // 异步回调(notify_url)参数处理规则，键为 App\Consts\Pay 常量
    'callback' => [
        Pay::IS_SIGN            => true,            // 开启签名验证
        Pay::IS_STATUS          => true,            // 开启状态验证
        Pay::FIELD_STATUS_KEY   => 'trade_status',  // 状态字段
        Pay::FIELD_STATUS_VALUE => 'TRADE_SUCCESS', // 成功状态值
        Pay::FIELD_ORDER_KEY    => 'out_trade_no',  // 商户订单号字段
        Pay::FIELD_AMOUNT_KEY   => 'money',         // 金额字段(单位:元)
        Pay::FIELD_RESPONSE     => 'success',       // 成功后返回给易支付的内容
    ],
];
