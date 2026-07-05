<?php
declare(strict_types=1);

namespace App\Service;

use Kernel\Annotation\Bind;

#[Bind(class: \App\Service\Bind\App::class)]
interface App
{
    /**
     * 本地安装插件（通过上传zip）
     * @param string $key   插件目录名
     * @param int    $type  0=通用 1=支付 2=主题
     * @param string $zipPath 已上传的zip绝对路径
     */
    public function installPlugin(string $key, int $type, string $zipPath): void;

    /**
     * 卸载插件
     * @param string $key
     * @param int    $type
     */
    public function uninstallPlugin(string $key, int $type): void;
}
