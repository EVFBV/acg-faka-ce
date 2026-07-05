<?php
declare(strict_types=1);

namespace App\Pay\AliPay;

use App\Entity\PayEntity;
use App\Pay\Base;
use App\Pay\Pay;
use Kernel\Exception\JSONException;

class AliPay extends Base implements Pay
{
    public function trade(): PayEntity
    {
        $config = $this->config;
        $appId = (string)($config['app_id'] ?? '');
        $privateKey = (string)($config['private_key'] ?? '');
        $alipayPublicKey = (string)($config['alipay_public_key'] ?? '');
        $sandbox = (string)($config['sandbox'] ?? '0');

        if (!$appId || !$privateKey || !$alipayPublicKey) {
            throw new JSONException("支付宝当面付配置不完整，请先填写AppID、私钥和支付宝公钥");
        }

        $gateway = $sandbox === '1'
            ? 'https://openapi-sandbox.dl.alipaydev.com/gateway.do'
            : 'https://openapi.alipay.com/gateway.do';

        $outTradeNo = $this->tradeNo;
        $totalAmount = number_format($this->amount, 2, '.', '');
        $subject = "订单 {$outTradeNo}";

        // 构建请求参数
        $bizContent = json_encode([
            'out_trade_no' => $outTradeNo,
            'total_amount' => $totalAmount,
            'subject'      => $subject,
        ]);

        $params = [
            'app_id'      => $appId,
            'method'      => 'alipay.trade.precreate',
            'charset'     => 'utf-8',
            'sign_type'   => 'RSA2',
            'timestamp'   => date('Y-m-d H:i:s'),
            'version'     => '1.0',
            'notify_url'  => $this->callbackUrl,
            'biz_content' => $bizContent,
        ];

        ksort($params);
        $signStr = '';
        foreach ($params as $k => $v) {
            $signStr .= $k . '=' . $v . '&';
        }
        $signStr = rtrim($signStr, '&');

        $pkeyId = openssl_get_privatekey("-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($privateKey, 64, "\n") . "-----END RSA PRIVATE KEY-----");
        openssl_sign($signStr, $signature, $pkeyId, OPENSSL_ALGO_SHA256);
        $params['sign'] = base64_encode($signature);

        $response = $this->http()->post($gateway, ['form_params' => $params]);
        $result = json_decode((string)$response->getBody(), true);
        $data = $result['alipay_trade_precreate_response'] ?? [];

        if (($data['code'] ?? '') !== '10000') {
            $this->log("支付宝下单失败: " . ($data['sub_msg'] ?? $data['msg'] ?? '未知错误'));
            throw new JSONException("支付宝下单失败：" . ($data['sub_msg'] ?? $data['msg'] ?? '请检查配置'));
        }

        $qrCode = $data['qr_code'];
        $entity = new PayEntity();
        $entity->setType(Pay::TYPE_LOCAL_RENDER);
        $entity->setBody('<div style="text-align:center;padding:20px;">
            <p style="font-size:16px;margin-bottom:10px;">请使用支付宝扫码支付</p>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrCode) . '" style="width:200px;height:200px;">
            <p style="color:#999;font-size:12px;margin-top:10px;">金额：¥' . $totalAmount . '</p>
        </div>');
        return $entity;
    }
}
