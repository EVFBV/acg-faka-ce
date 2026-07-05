<?php
declare(strict_types=1);

namespace App\Service\Captcha;

use App\Model\Config;
use App\Service\Captcha\Driver\Aliyun;
use App\Service\Captcha\Driver\Geetest;
use App\Service\Captcha\Driver\Turnstile;
use App\Util\Client;

/**
 * 行为验证工厂
 * 负责根据后台配置实例化对应驱动，并提供统一的校验入口。
 * 新增服务商时，只需实现 Provider 接口并在 DRIVERS 中注册即可。
 */
class Factory
{
    /**
     * 已注册的驱动 key => 类名
     * 后期扩展新的人机验证服务，只需在此登记
     * @var array<string,class-string<Provider>>
     */
    public const DRIVERS = [
        "geetest" => Geetest::class,
        "turnstile" => Turnstile::class,
        "aliyun" => Aliyun::class,
    ];

    /**
     * 读取整体行为验证配置(JSON)
     * @return array
     */
    public static function config(): array
    {
        $raw = Config::get("behavior_captcha_config");
        if ($raw === "") {
            return [];
        }
        $config = json_decode($raw, true);
        return is_array($config) ? $config : [];
    }

    /**
     * 当前启用的验证类型：image(原数字验证码) 或某个行为验证驱动 key
     * @return string
     */
    public static function currentType(): string
    {
        $config = self::config();
        $type = trim((string)($config['type'] ?? "image"));
        return $type === "" ? "image" : $type;
    }

    /**
     * 是否启用了行为验证(非原生图形验证码)
     * @return bool
     */
    public static function isBehavior(): bool
    {
        $type = self::currentType();
        return $type !== "image" && isset(self::DRIVERS[$type]);
    }

    /**
     * 实例化当前启用的驱动
     * @return Provider|null 未启用行为验证时返回 null
     */
    public static function make(): ?Provider
    {
        $type = self::currentType();
        if (!isset(self::DRIVERS[$type])) {
            return null;
        }
        $config = self::config();
        $driverConfig = (array)($config[$type] ?? []);
        $class = self::DRIVERS[$type];
        return new $class($driverConfig);
    }

    /**
     * 统一校验入口
     * 若未启用行为验证则返回 null(交由调用方回退到原数字验证码逻辑)
     * @param array $params 前端提交的参数
     * @return bool|null true=通过 false=不通过 null=未启用行为验证
     */
    public static function verify(array $params): ?bool
    {
        $provider = self::make();
        if ($provider === null) {
            return null;
        }
        return $provider->verify($params, Client::getAddress());
    }

    /**
     * 返回暴露给前端的公开配置(含当前类型及公钥，无密钥)
     * @return array
     */
    public static function frontendConfig(): array
    {
        $provider = self::make();
        if ($provider === null) {
            return ["type" => "image"];
        }
        return $provider->frontendConfig();
    }
}
