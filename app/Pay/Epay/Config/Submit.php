<?php
declare(strict_types=1);
return [
    ['name' => 'api_url',  'title' => '接口地址', 'type' => 'input',  'default' => 'https://pay.example.com', 'desc' => '易支付接口根地址，末尾不加/'],
    ['name' => 'pid',      'title' => '商户ID',   'type' => 'input',  'default' => '', 'desc' => '易支付商户ID'],
    ['name' => 'key',      'title' => '商户密钥', 'type' => 'input',  'default' => '', 'desc' => '易支付商户密钥'],
    ['name' => 'pay_type', 'title' => '支付类型', 'type' => 'select', 'default' => 'alipay',
        'options' => [
            ['label' => '支付宝', 'value' => 'alipay'],
            ['label' => '微信',   'value' => 'wxpay'],
            ['label' => 'QQ钱包', 'value' => 'qqpay'],
        ],
        'desc' => '选择支付通道'
    ],
];
