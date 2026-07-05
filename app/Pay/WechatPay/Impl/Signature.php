<?php
declare(strict_types=1);

namespace App\Pay\WechatPay\Impl;

use App\Consts\Pay as PayConsts;
use App\Pay\Signature as SignatureInterface;
use Kernel\Util\Context;

class Signature implements SignatureInterface
{
    /**
     * 微信支付V3异步通知处理：
     * 通知报文为 AES-256-GCM 加密结构，此处用 APIv3 密钥(api_key)解密
     * resource.ciphertext，并将解密后的明文关键字段(out_trade_no、trade_state、
     * amount_total[元])回写到回调上下文，供后续订单号/状态/金额校验使用。
     *
     * 说明：微信V3官方验签需依赖 HTTP 头(Wechatpay-Signature/Timestamp/Nonce/Serial)
     * 与平台证书，而本接口仅接收报文体，故此处以 APIv3 密钥解密成功作为可信凭据。
     *
     * @param array $data 回调报文(JSON解码后的数组)
     * @param array $config 插件配置(Config.php)
     * @return bool
     */
    public function verification(array $data, array $config): bool
    {
        $apiKey = (string)($config['api_key'] ?? '');
        $resource = $data['resource'] ?? null;
        if ($apiKey === '' || !is_array($resource)) {
            return false;
        }

        $ciphertext = base64_decode((string)($resource['ciphertext'] ?? ''), true);
        $nonce = (string)($resource['nonce'] ?? '');
        $aad = (string)($resource['associated_data'] ?? '');
        if ($ciphertext === false || $nonce === '' || strlen($ciphertext) <= 16) {
            return false;
        }

        // AES-256-GCM：密文尾部16字节为认证标签(tag)
        $tag = substr($ciphertext, -16);
        $realCipher = substr($ciphertext, 0, -16);
        $plaintext = openssl_decrypt(
            $realCipher,
            'aes-256-gcm',
            $apiKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $aad
        );
        if ($plaintext === false) {
            return false;
        }

        $decoded = json_decode($plaintext, true);
        if (!is_array($decoded)) {
            return false;
        }

        // 将解密后的关键字段回写到上下文，金额由分转为元
        $map = Context::get(PayConsts::DAFA);
        if (!is_array($map)) {
            $map = $data;
        }
        $map['out_trade_no'] = $decoded['out_trade_no'] ?? '';
        $map['trade_state'] = $decoded['trade_state'] ?? '';
        $amountTotalFen = (int)($decoded['amount']['total'] ?? 0);
        $map['amount_total'] = number_format($amountTotalFen / 100, 2, '.', '');
        Context::set(PayConsts::DAFA, $map);

        return true;
    }
}
