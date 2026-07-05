<?php
declare(strict_types=1);

namespace App\Service\Captcha;

/**
 * 行为验证驱动接口
 * 所有第三方人机验证服务(极验/Cloudflare Turnstile/阿里云等)均需实现此接口
 * 后期新增服务商时，只需新增一个实现类并在 Factory 中注册即可
 */
interface Provider
{
    /**
     * 驱动唯一标识(与前端 captcha_type、配置存储的 key 保持一致)
     * @return string
     */
    public function key(): string;

    /**
     * 驱动展示名称
     * @return string
     */
    public function name(): string;

    /**
     * 校验前端提交的人机验证凭证
     * @param array $params 前端随表单一并提交的参数(如 token、challenge 等)
     * @param string $clientIp 客户端IP
     * @return bool 通过返回 true，失败返回 false
     */
    public function verify(array $params, string $clientIp = ""): bool;

    /**
     * 返回暴露给前端的公开配置(如 site_key / captcha_id)，不得包含任何密钥
     * @return array
     */
    public function frontendConfig(): array;
}
