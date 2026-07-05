<?php
declare(strict_types=1);

namespace App\Service\Bind;

use App\Util\File;
use App\Util\Zip;
use Kernel\Exception\JSONException;
use Kernel\Util\Plugin;
use Kernel\Util\SQL;

class App implements \App\Service\App
{
    /**
     * 本地上传zip安装插件
     * type: 0=通用插件, 1=支付插件, 2=网站主题
     */
    public function installPlugin(string $key, int $type, string $zipPath): void
    {
        if ($type === 1) {
            $pluginPath = BASE_PATH . "/app/Pay/{$key}/";
            $fileInit = file_exists($pluginPath . "Config/Info.php");
        } elseif ($type === 2) {
            $pluginPath = BASE_PATH . "/app/View/User/Theme/{$key}/";
            $fileInit = file_exists($pluginPath . "Config.php");
        } else {
            $pluginPath = BASE_PATH . "/app/Plugin/{$key}/";
            $fileInit = file_exists($pluginPath . "Config/Info.php");
        }

        if (!is_dir($pluginPath)) {
            mkdir($pluginPath, 0777, true);
        }

        if ($fileInit) {
            throw new JSONException("该插件已被安装，请勿重复安装");
        }

        if (!Zip::unzip($zipPath, $pluginPath)) {
            throw new JSONException("安装失败，请检查是否有写入权限");
        }

        unlink($zipPath);

        $installSql = $pluginPath . "install.sql";
        if (file_exists($installSql)) {
            $database = config("database");
            SQL::import($installSql, $database['host'], $database['database'], $database['username'], $database['password'], $database['prefix']);
        }

        if ($type === 0) {
            Plugin::runHookState($key, \Kernel\Annotation\Plugin::INSTALL);
        }
    }

    /**
     * 卸载插件
     */
    public function uninstallPlugin(string $key, int $type): void
    {
        if ($type === 1) {
            $pluginPath = BASE_PATH . "/app/Pay/{$key}/";
        } elseif ($type === 2) {
            $pluginPath = BASE_PATH . "/app/View/User/Theme/{$key}/";
        } else {
            $pluginPath = BASE_PATH . "/app/Plugin/{$key}/";
        }

        if (is_dir($pluginPath)) {
            File::delDirectory($pluginPath);
        }
    }
}
