<?php
declare(strict_types=1);

namespace App\Pay\Epay;

use App\Entity\PayEntity;
use App\Pay\Base;
use App\Pay\Pay;
use Kernel\Exception\JSONException;

class Epay extends Base implements Pay
{
    public function trade(): PayEntity
    {
        $config  = $this->config;
        $apiUrl  = rtrim((string)($config['api_url'] ?? ''), '/');
        $pid     = (string)($config['pid']      ?? '');
        $key     = (string)($config['key']      ?? '');
        $payType = (string)($config['pay_type'] ?? 'alipay');

        if (!$apiUrl || !$pid || !$key) {
            throw new JSONException("易支付配置不完整，请填写接口地址、商户ID和密钥");
        }

        $params = [
            'pid'          => $pid,
            'type'         => $payType,
            'out_trade_no' => $this->tradeNo,
            'notify_url'   => $this->callbackUrl,
            'return_url'   => $this->returnUrl,
            'name'         => "订单 " . $this->tradeNo,
            'money'        => number_format($this->amount, 2, '.', ''),
            'clientip'     => $this->clientIp,
            'device'       => 'pc',
        ];

        ksort($params);
        $signStr = http_build_query($params);
        $params['sign']      = md5($signStr . $key);
        $params['sign_type'] = 'MD5';

        $entity = new PayEntity();
        $entity->setType(Pay::TYPE_REDIRECT);
        $entity->setBody($apiUrl . '/submit.php?' . http_build_query($params));
        return $entity;
    }
}
