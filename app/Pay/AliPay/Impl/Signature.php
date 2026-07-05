<?php
declare(strict_types=1);

namespace App\Pay\AliPay\Impl;

use App\Pay\Signature as SignatureInterface;

class Signature implements SignatureInterface
{
    /**
     * 支付宝异步通知验签(RSA2)：
     * 将除 sign、sign_type 及空值外的参数按键名ASCII升序排序，
     * 拼接为 k=v&k=v（原始值不做URL编码），
     * 用支付宝公钥对该字符串做 SHA256withRSA 验签。
     * @param array $data 回调参数
     * @param array $config 插件配置(Config.php)
     * @return bool
     */
    public function verification(array $data, array $config): bool
    {
        $sign      = (string)($data['sign'] ?? '');
        $publicKey = (string)($config['alipay_public_key'] ?? '');
        if ($sign === '' || $publicKey === '') {
            return false;
        }

        $params = $data;
        unset($params['sign'], $params['sign_type']);
        ksort($params);

        $signStr = '';
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $signStr .= $k . '=' . $v . '&';
        }
        $signStr = rtrim($signStr, '&');

        $pem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split($publicKey, 64, "\n")
            . "-----END PUBLIC KEY-----";

        $result = openssl_verify($signStr, base64_decode($sign), $pem, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }
}
