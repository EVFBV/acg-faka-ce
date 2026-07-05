<?php
declare(strict_types=1);

namespace App\Pay\WechatPay;

use App\Entity\PayEntity;
use App\Pay\Base;
use App\Pay\Pay;
use Kernel\Exception\JSONException;

class WechatPay extends Base implements Pay
{
    public function trade(): PayEntity
    {
        $config = $this->config;
        $mchId  = (string)($config['mch_id']  ?? '');
        $apiKey = (string)($config['api_key']  ?? '');
        $appId  = (string)($config['app_id']   ?? '');

        if (!$mchId || !$apiKey || !$appId) {
            throw new JSONException("微信支付配置不完整，请先填写商户号、APIv3密钥和AppID");
        }

        $outTradeNo  = $this->tradeNo;
        $totalFee    = (int)round($this->amount * 100); // 单位：分

        $body = [
            'appid'        => $appId,
            'mchid'        => $mchId,
            'description'  => "订单 {$outTradeNo}",
            'out_trade_no' => $outTradeNo,
            'notify_url'   => $this->callbackUrl,
            'amount'       => ['total' => $totalFee, 'currency' => 'CNY'],
        ];

        $url = 'https://api.mch.weixin.qq.com/v3/pay/transactions/native';

        try {
            $response = $this->http()->post($url, [
                'json'    => $body,
                'headers' => $this->buildHeaders($mchId, $apiKey, $appId, $body),
            ]);
            $result = json_decode((string)$response->getBody(), true);
        } catch (\Throwable $e) {
            $this->log("微信下单失败: " . $e->getMessage());
            throw new JSONException("微信支付下单失败：" . $e->getMessage());
        }

        $codeUrl = $result['code_url'] ?? '';
        if (!$codeUrl) {
            throw new JSONException("微信支付下单失败：未获取到二维码链接");
        }

        $entity = new PayEntity();
        $entity->setType(Pay::TYPE_LOCAL_RENDER);
        $entity->setBody('<div style="text-align:center;padding:20px;">
            <p style="font-size:16px;margin-bottom:10px;">请使用微信扫码支付</p>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($codeUrl) . '" style="width:200px;height:200px;">
            <p style="color:#999;font-size:12px;margin-top:10px;">金额：¥' . number_format($this->amount, 2) . '</p>
        </div>');
        return $entity;
    }

    private function buildHeaders(string $mchId, string $apiKey, string $appId, array $body): array
    {
        $timestamp  = time();
        $nonceStr   = bin2hex(random_bytes(16));
        $bodyStr    = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $message    = "POST\n/v3/pay/transactions/native\n{$timestamp}\n{$nonceStr}\n{$bodyStr}\n";

        $keyPem = (string)($this->config['key_pem'] ?? '');
        $pkeyId = openssl_get_privatekey($keyPem);
        openssl_sign($message, $signature, $pkeyId, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($signature);

        // 获取证书序列号
        $certPem    = (string)($this->config['cert_pem'] ?? '');
        $certInfo   = openssl_x509_parse($certPem);
        $serialNo   = strtoupper((string)($certInfo['serialNumberHex'] ?? ''));

        return [
            'Authorization' => sprintf(
                'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%s",serial_no="%s",signature="%s"',
                $mchId, $nonceStr, $timestamp, $serialNo, $sign
            ),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }
}
