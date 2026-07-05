<?php
declare(strict_types=1);
return [
    [
        'name'    => 'app_id',
        'title'   => 'AppID',
        'type'    => 'input',
        'default' => '',
        'desc'    => '支付宝开放平台应用ID',
    ],
    [
        'name'    => 'private_key',
        'title'   => '应用私钥',
        'type'    => 'textarea',
        'default' => '',
        'desc'    => 'RSA2应用私钥（PKCS8格式，不含头尾）',
    ],
    [
        'name'    => 'alipay_public_key',
        'title'   => '支付宝公钥',
        'type'    => 'textarea',
        'default' => '',
        'desc'    => '支付宝公钥（用于验签）',
    ],
    [
        'name'    => 'sandbox',
        'title'   => '沙箱模式',
        'type'    => 'select',
        'default' => '0',
        'options' => [['label' => '关闭', 'value' => '0'], ['label' => '开启(测试)', 'value' => '1']],
        'desc'    => '开启后使用沙箱环境',
    ],
];
