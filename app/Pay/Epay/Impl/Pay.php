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

        // 按彩虹易支付文档，money 单位为元，最多2位小数；name 超127字节自动截取
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

        // 易支付签名：按键名ASCII升序排序，过滤空值及sign/sign_type，
        // 拼接为 k=v&k=v（原始值，不做URL编码），末尾拼接密钥后取MD5
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

        // submit.php 需要 POST 表单提交，使用 TYPE_SUBMIT 由系统渲染表单自动提交
        $entity = new PayEntity();
        $entity->setType(PayInterface::TYPE_SUBMIT);
        $entity->setUrl($apiUrl . '/submit.php');
        $entity->setOption($params);
        return $entity;
    }
}
