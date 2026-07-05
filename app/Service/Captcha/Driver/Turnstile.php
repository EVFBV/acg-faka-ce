<?php
declare(strict_types=1);

namespace App\Service\Captcha\Driver;

use App\Service\Captcha\AbstractProvider;
use App\Util\Http;

/**
 * Cloudflare Turnstile 人机验证驱动
 * 文档: https://developers.cloudflare.com/turnstile/
 * 前端提交字段: cf-turnstile-response
 */
class Turnstile extends AbstractProvider
{
    public function key(): string
    {
        return "turnstile";
    }

    public function name(): string
    {
        return "Cloudflare Turnstile";
    }

    public function verify(array $params, string $clientIp = ""): bool
    {
        $secret = $this->cfg("secret");
        $token = trim((string)($params['cf-turnstile-response'] ?? $params['token'] ?? ''));
        if ($secret === '' || $token === '') {
            return false;
        }

        try {
            $form = [
                'secret' => $secret,
                'response' => $token,
            ];
            if ($clientIp !== '') {
                $form['remoteip'] = $clientIp;
            }
            $response = Http::make()->post("https://challenges.cloudflare.com/turnstile/v0/siteverify", [
                'form_params' => $form,
            ]);
            $result = json_decode((string)$response->getBody(), true);
            return isset($result['success']) && $result['success'] === true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function frontendConfig(): array
    {
        return [
            "type" => $this->key(),
            "site_key" => $this->cfg("site_key"),
        ];
    }
}
