<?php
declare(strict_types=1);

namespace App\Service\Captcha\Driver;

use App\Service\Captcha\AbstractProvider;
use App\Util\Http;

/**
 * 阿里云 验证码2.0 驱动
 * 文档: https://help.aliyun.com/document_detail/193141.html
 * 前端提交字段: captcha_verify_param (由阿里云JS bind回调返回的验证参数)
 *
 * 说明: 阿里云验证码2.0 服务端校验走 OpenAPI(VerifyIntelligentCaptcha)，
 * 需要 AccessKeyId / AccessKeySecret / SceneId。此处采用 RPC 签名(HMAC-SHA1)方式调用。
 */
class Aliyun extends AbstractProvider
{
    public function key(): string
    {
        return "aliyun";
    }

    public function name(): string
    {
        return "阿里云验证码";
    }

    public function verify(array $params, string $clientIp = ""): bool
    {
        $accessKeyId = $this->cfg("access_key_id");
        $accessKeySecret = $this->cfg("access_key_secret");
        $sceneId = $this->cfg("scene_id");
        $region = $this->cfg("region", "cn-hangzhou");

        $verifyParam = trim((string)($params['captcha_verify_param'] ?? ''));
        if ($accessKeyId === '' || $accessKeySecret === '' || $verifyParam === '') {
            return false;
        }

        try {
            $endpoint = "https://captcha.{$region}.aliyuncs.com/";
            $query = [
                "AccessKeyId" => $accessKeyId,
                "Action" => "VerifyIntelligentCaptcha",
                "Format" => "JSON",
                "RegionId" => $region,
                "SceneId" => $sceneId,
                "CaptchaVerifyParam" => $verifyParam,
                "SignatureMethod" => "HMAC-SHA1",
                "SignatureNonce" => bin2hex(random_bytes(16)),
                "SignatureVersion" => "1.0",
                "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
                "Version" => "2020-11-01",
            ];

            ksort($query);
            $canonical = "";
            foreach ($query as $k => $v) {
                $canonical .= "&" . $this->percentEncode($k) . "=" . $this->percentEncode((string)$v);
            }
            $stringToSign = "GET&%2F&" . $this->percentEncode(substr($canonical, 1));
            $signature = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&", true));
            $query["Signature"] = $signature;

            $response = Http::make()->get($endpoint, [
                "query" => $query,
                "timeout" => 5,
            ]);
            $result = json_decode((string)$response->getBody(), true);
            // 返回 Result.VerifyResult 为 true 表示通过
            return isset($result['Result']['VerifyResult']) && $result['Result']['VerifyResult'] === true;
        } catch (\Throwable $e) {
            // 校验服务异常时放行，避免阻塞业务
            return true;
        }
    }

    private function percentEncode(string $value): string
    {
        $res = urlencode($value);
        $res = str_replace(["+", "*"], ["%20", "%2A"], $res);
        $res = str_replace("%7E", "~", $res);
        return $res;
    }

    public function frontendConfig(): array
    {
        return [
            "type" => $this->key(),
            "scene_id" => $this->cfg("scene_id"),
            "prefix" => $this->cfg("prefix"),
        ];
    }
}
