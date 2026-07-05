<?php
declare(strict_types=1);

namespace App\Service\Captcha;

use App\Model\Config;

/**
 * 行为验证驱动抽象基类
 * 统一从数据库 behavior_captcha_config(JSON) 读取各驱动配置
 */
abstract class AbstractProvider implements Provider
{
    /**
     * @var array 当前驱动的配置
     */
    protected array $config;

    /**
     * @param array $config 当前驱动的配置(已从整体配置中取出对应驱动的段)
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * 读取配置项
     * @param string $key
     * @param string $default
     * @return string
     */
    protected function cfg(string $key, string $default = ""): string
    {
        return isset($this->config[$key]) ? trim((string)$this->config[$key]) : $default;
    }
}
