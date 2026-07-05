<?php
declare(strict_types=1);

use App\Consts\Pay;

return [
    'name'    => 'USDT加密货币支付',
    'version' => '1.0.0',
    'author'  => 'System Built-in',
    'desc'    => 'USDT-TRC20/ERC20 加密货币收款，需要填写钱包地址。链上转账无第三方异步通知，订单需由管理员在后台手动确认到账。',
    'options' => [
        'TRC20' => 'USDT-TRC20(波场)',
        'ERC20' => 'USDT-ERC20(以太坊)',
    ],
    // USDT为链上转账，无可信的第三方异步回调。
    // 为防止公开回调地址(/user/api/order/callback.USDT)被伪造请求刷单，
    // 开启签名验证并由 Impl/Signature 一律拒绝，订单仅允许后台手动确认。
    'callback' => [
        Pay::IS_SIGN            => true,   // 开启签名验证(实际一律拒绝)
        Pay::IS_STATUS          => false,
        Pay::FIELD_STATUS_KEY   => 'status',
        Pay::FIELD_STATUS_VALUE => 'success',
        Pay::FIELD_ORDER_KEY    => 'out_trade_no',
        Pay::FIELD_AMOUNT_KEY   => 'money',
        Pay::FIELD_RESPONSE     => 'success',
    ],
];
