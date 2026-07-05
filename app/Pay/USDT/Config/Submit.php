<?php
declare(strict_types=1);
return [
    ['name' => 'network',        'title' => '网络类型',     'type' => 'select',  'default' => 'TRC20',
        'options' => [['label' => 'TRC20 (TRON)', 'value' => 'TRC20'], ['label' => 'ERC20 (ETH)', 'value' => 'ERC20']],
        'desc' => '选择收款网络'],
    ['name' => 'wallet_address', 'title' => '收款钱包地址', 'type' => 'input',   'default' => '', 'desc' => '你的USDT收款地址'],
    ['name' => 'usdt_rate',      'title' => 'USDT汇率',    'type' => 'input',   'default' => '7.2', 'desc' => '1 USDT = ? 人民币，用于换算金额'],
    ['name' => 'confirm_tip',    'title' => '付款提示',     'type' => 'textarea','default' => '请在30分钟内完成付款，付款后请联系客服核验', 'desc' => '显示给用户的付款说明'],
];
