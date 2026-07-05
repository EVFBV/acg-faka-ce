<?php
declare(strict_types=1);

namespace App\Pay\USDT\Impl;

use App\Entity\PayEntity;
use App\Pay\Base;
use App\Pay\Pay as PayInterface;
use Kernel\Exception\JSONException;

class Pay extends Base implements PayInterface
{
    public function trade(): PayEntity
    {
        $config  = $this->config;
        $address = trim((string)($config['wallet_address'] ?? ''));
        $network = (string)($config['network']     ?? 'TRC20');
        $rate    = (float)($config['usdt_rate']    ?? 7.2);
        $tip     = (string)($config['confirm_tip'] ?? '');

        if (!$address) {
            throw new JSONException("USDT收款地址未配置，请先在支付插件设置中填写钱包地址");
        }

        $usdtAmount = $rate > 0 ? round($this->amount / $rate, 2) : $this->amount;

        $entity = new PayEntity();
        $entity->setType(PayInterface::TYPE_LOCAL_RENDER);
        $entity->setUrl('
<div style="text-align:center;padding:20px;font-family:sans-serif;">
    <p style="font-size:18px;font-weight:bold;margin-bottom:6px;">USDT 加密货币支付</p>
    <p style="color:#555;margin-bottom:16px;">网络：<b>' . htmlspecialchars($network) . '</b> | 金额：<b>' . $usdtAmount . ' USDT</b>（≈ ¥' . number_format($this->amount, 2) . '）</p>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($address) . '" style="width:180px;height:180px;border:4px solid #eee;border-radius:8px;">
    <p style="margin-top:12px;word-break:break-all;font-size:13px;color:#333;background:#f5f5f5;padding:8px 12px;border-radius:6px;">' . htmlspecialchars($address) . '</p>
    ' . ($tip ? '<p style="color:#e67e22;font-size:13px;margin-top:10px;">' . nl2br(htmlspecialchars($tip)) . '</p>' : '') . '
    <p style="color:#aaa;font-size:11px;margin-top:8px;">订单号：' . htmlspecialchars($this->tradeNo) . '</p>
</div>');
        return $entity;
    }
}
