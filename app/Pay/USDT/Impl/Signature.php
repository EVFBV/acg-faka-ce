<?php
declare(strict_types=1);

namespace App\Pay\USDT\Impl;

use App\Pay\Signature as SignatureInterface;

class Signature implements SignatureInterface
{
    /**
     * USDT为链上转账，无第三方可信异步通知。
     * 公开回调地址不可信，故一律拒绝，防止伪造请求刷单。
     * 订单到账需由管理员在后台手动确认。
     *
     * @param array $data
     * @param array $config
     * @return bool
     */
    public function verification(array $data, array $config): bool
    {
        return false;
    }
}
