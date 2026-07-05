<?php
declare(strict_types=1);

namespace App\Pay\Epay\Impl;

use App\Entity\PayEntity;
use App\Pay\Base;
use App\Pay\Pay as PayInterface;
use Kernel\Exception\JSONException;

class Pay extends Base implements PayInterface
{
    public function trade(): PayEntity
    {
        $config  = $this->config;
        $apiUrl  = rtrim((string)($config['api_url'] ?? ''), '/');
        $pid     = (string)($config['pid']      ?? '');
        $key     = (string)($config['key']      ?? '');

        // 支付方式(type)优先使用管理员为该通道设置的通道编码(code)，
        // 若通道编码为空则回退到插件配置里的 pay_type，最终兜底 alipay
        $payType = trim((string)($this->code ?? ''));
        if ($payType === '') {
            $payType = trim((string)($config['pay_type'] ?? ''));
        }
        if ($payType === '') {
            $payType = 'alipay';
        }

        if (!$apiUrl || !$pid || !$key) {
            throw new JSONException("易支付配置不完整，请填写接口地址、商户ID和密钥");
        }

        // 按彩虹易支付文档 API接口支付(mapi.php)，money 单位为元、最多2位小数；
        // name 超127字节自动截取；clientip 必填
        $params = [
            'pid'          => $pid,
            'type'         => $payType,
            'out_trade_no' => $this->tradeNo,
            'notify_url'   => $this->callbackUrl,
            'return_url'   => $this->returnUrl,
            'name'         => "订单 " . $this->tradeNo,
            'money'        => number_format($this->amount, 2, '.', ''),
            'clientip'     => $this->clientIp ?: '127.0.0.1',
            'device'       => 'pc',
        ];

        // 易支付签名：按键名ASCII升序排序，过滤空值及sign/sign_type，
        // 拼接为 k=v&k=v（原始值，不做URL编码），末尾拼接密钥后取MD5（小写）
        ksort($params);
        $signStr = '';
        foreach ($params as $k => $v) {
            if ($v === '' || $k === 'sign' || $k === 'sign_type') {
                continue;
            }
            $signStr .= $k . '=' . $v . '&';
        }
        $signStr             = rtrim($signStr, '&');
        $params['sign']      = md5($signStr . $key);
        $params['sign_type'] = 'MD5';

        // API接口支付：服务端 POST 到 mapi.php，返回 JSON
        try {
            $response = $this->http()->post($apiUrl . '/mapi.php', ['form_params' => $params]);
            $result   = json_decode((string)$response->getBody(), true);
        } catch (\Throwable $e) {
            $this->log("易支付请求失败: " . $e->getMessage());
            throw new JSONException("易支付请求失败，请检查接口地址是否正确");
        }

        if (!is_array($result) || (int)($result['code'] ?? 0) !== 1) {
            $msg = is_array($result) ? ($result['msg'] ?? '未知错误') : '返回数据异常';
            $this->log("易支付下单失败: " . $msg);
            throw new JSONException("易支付下单失败：" . $msg);
        }

        $entity = new PayEntity();

        // 返回结果中 payurl / qrcode / urlscheme 三者只会返回其一
        if (!empty($result['payurl'])) {
            // 直接跳转支付
            $entity->setType(PayInterface::TYPE_REDIRECT);
            $entity->setUrl((string)$result['payurl']);
            return $entity;
        }

        if (!empty($result['urlscheme'])) {
            // 小程序跳转，使用跳转方式
            $entity->setType(PayInterface::TYPE_REDIRECT);
            $entity->setUrl((string)$result['urlscheme']);
            return $entity;
        }

        if (!empty($result['qrcode'])) {
            // 返回二维码内容，本地渲染二维码支付页
            $qrCode = (string)$result['qrcode'];
            $entity->setType(PayInterface::TYPE_LOCAL_RENDER);
            $entity->setUrl('<div style="text-align:center;padding:20px;">
                <p style="font-size:16px;margin-bottom:10px;">请扫码完成支付</p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($qrCode) . '" style="width:220px;height:220px;">
                <p style="color:#999;font-size:12px;margin-top:10px;">金额：¥' . $params['money'] . '</p>
            </div>');
            return $entity;
        }

        $this->log("易支付返回无有效支付参数: " . json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        throw new JSONException("易支付未返回有效的支付信息");
    }
}
