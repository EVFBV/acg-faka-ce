<?php
declare(strict_types=1);

namespace App\Service\Captcha;

use App\Util\Captcha;

/**
 * 统一人机验证入口
 * 各业务场景(登录/注册/下单/发码前置等)统一调用 check()。
 * - 当后台启用了行为验证(极验/Turnstile/阿里云等)时，走第三方服务端校验；
 * - 否则回退到系统原生数字图形验证码。
 */
class Verifier
{
    /**
     * 校验人机验证
     * @param array $params 前端随表单一并提交的参数($_POST)
     * @param string $sessionName 图形验证码场景名(仅在回退到图形验证码时使用)
     * @return bool
     */
    public static function check(array $params, string $sessionName): bool
    {
        $result = Factory::verify($params);
        if ($result !== null) {
            //已启用行为验证
            return $result;
        }
        //回退到原生数字图形验证码
        return Captcha::check((int)($params['captcha'] ?? 0), $sessionName);
    }

    /**
     * 销毁场景残留(图形验证码 session)。行为验证无状态，无需销毁。
     * @param string $sessionName
     * @return void
     */
    public static function destroy(string $sessionName): void
    {
        Captcha::destroy($sessionName);
    }
}
