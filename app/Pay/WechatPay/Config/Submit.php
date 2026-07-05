<?php
declare(strict_types=1);
return [
    ['name' => 'mch_id',   'title' => '商户号',    'type' => 'input',    'default' => '', 'desc' => '微信支付商户号'],
    ['name' => 'api_key',  'title' => 'APIv3密钥', 'type' => 'input',    'default' => '', 'desc' => '微信支付APIv3密钥（32位）'],
    ['name' => 'app_id',   'title' => 'AppID',    'type' => 'input',    'default' => '', 'desc' => '公众号/小程序/APP的AppID'],
    ['name' => 'cert_pem', 'title' => '商户证书', 'type' => 'textarea', 'default' => '', 'desc' => '商户API证书内容（apiclient_cert.pem 全文）'],
    ['name' => 'key_pem',  'title' => '证书私钥', 'type' => 'textarea', 'default' => '', 'desc' => '商户API私钥内容（apiclient_key.pem 全文）'],
];
