<?php
declare(strict_types=1);

use App\Consts\Pay;

return [
    'name'    => '微信扫码支付',
    'version' => '1.0.0',
    'author'  => 'System Built-in',
    'desc'    => '官方微信支付 Native 扫码，需要申请微信商户号并开通 Native 支付。注意：微信V3异步通知为AES-GCM加密报文，需在服务器可正常接收回调的环境下使用。',
    'options' => [
        'NATIVE' => '扫码支付(Native)',
    ],
    // 异步回调(notify_url)参数处理规则，键为 App\Consts\Pay 常量
    // 微信V3通知报文为加密结构，签名与解密统一在 Impl/Signature 中处理，
    // 解密后的明文字段(out_trade_no/amount等)会写回上下文供后续校验。
    'callback' => [
        Pay::IS_SIGN            => true,             // 开启签名验证+解密
        Pay::IS_STATUS          => true,             // 开启状态验证
        Pay::FIELD_STATUS_KEY   => 'trade_state',    // 交易状态字段
        Pay::FIELD_STATUS_VALUE => 'SUCCESS',        // 成功状态值
        Pay::FIELD_ORDER_KEY    => 'out_trade_no',   // 商户订单号字段
        Pay::FIELD_AMOUNT_KEY   => 'amount_total',   // 金额字段(单位:元)
        Pay::FIELD_RESPONSE     => '{"code":"SUCCESS","message":"成功"}', // 返回给微信的内容
    ],
];
