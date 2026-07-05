<?php
declare(strict_types=1);

namespace App\Pay\Epay\Impl;

use App\Pay\Signature as SignatureInterface;

class Signature implements SignatureInterface
{
    /**
     * 彩虹易支付异步通知验签：
     * 将除 sign、sign_type 及空值外的参数按键名ASCII升序排序，
     * 拼接为 k=v&k=v（原始值不做URL编码），末尾拼接商户密钥KEY后取MD5(小写)，
     * 与通知中的 sign 比对。
     * @param array $data 回调参数
     * @param array $config 插件配置(Config.php)
     * @return bool
     */
    public function verification(array $data, array $config): bool
    {
        $sign = (string)($data['sign'] ?? '');
        $key  = (string)($config['key'] ?? '');
        if ($sign === '' || $key === '') {
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

        return md5($signStr . $key) === strtolower($sign);
    }
}
