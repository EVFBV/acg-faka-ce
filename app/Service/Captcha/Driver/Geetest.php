<?php
declare(strict_types=1);

namespace App\Service\Captcha\Driver;

use App\Service\Captcha\AbstractProvider;
use App\Util\Http;

/**
 * 极验 行为验证 v4 驱动
 * 文档: https://docs.geetest.com/gt4/apirefer/api/server/
 * 前端提交字段: lot_number, captcha_output, pass_token, gen_time (由极验JS回调返回)
 */
class Geetest extends AbstractProvider
{
    public function key(): string
    {
        return "geetest";
    }

    public function name(): string
    {
        return "极验行为验证";
    }

    public function verify(array $params, string $clientIp = ""): bool
    {
        $captchaId = $this->cfg("captcha_id");
        $captchaKey = $this->cfg("captcha_key");

        $lotNumber = trim((string)($params['lot_number'] ?? ''));
        $captchaOutput = trim((string)($params['captcha_output'] ?? ''));
        $passToken = trim((string)($params['pass_token'] ?? ''));
        $genTime = trim((string)($params['gen_time'] ?? ''));

        if ($captchaId === '' || $captchaKey === '' || $lotNumber === '' || $captchaOutput === '') {
            return false;
        }

        try {
            // 生成签名: 使用 captcha_key 对 lot_number 做 HMAC-SHA256
            $signToken = hash_hmac("sha256", $lotNumber, $captchaKey);
            $response = Http::make()->post("http://gcaptcha4.geetest.com/validate?captcha_id=" . urlencode($captchaId), [
                'form_params' => [
                    'lot_number' => $lotNumber,
                    'captcha_output' => $captchaOutput,
                    'pass_token' => $passToken,
                    'gen_time' => $genTime,
                    'sign_token' => $signToken,
                ],
                'timeout' => 5,
            ]);
            $result = json_decode((string)$response->getBody(), true);
            return isset($result['result']) && $result['result'] === "success";
        } catch (\Throwable $e) {
            // 极验官方建议: 校验接口异常时放行(failback)，避免因极验服务不可用导致业务不可用
            return true;
        }
    }

    public function frontendConfig(): array
    {
        return [
            "type" => $this->key(),
            "captcha_id" => $this->cfg("captcha_id"),
        ];
    }
}
