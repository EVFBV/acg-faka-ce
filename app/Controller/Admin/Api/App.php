<?php
declare(strict_types=1);

namespace App\Controller\Admin\Api;

use App\Interceptor\ManageSession;
use App\Interceptor\Waf;
use App\Model\ManageLog;
use Kernel\Annotation\Inject;
use Kernel\Annotation\Interceptor;
use Kernel\Exception\JSONException;

#[Interceptor([Waf::class, ManageSession::class], Interceptor::TYPE_API)]
class App extends Manage
{
    #[Inject]
    private \App\Service\App $app;

    /**
     * 本地上传zip包安装插件
     * POST: plugin_key, type, file(zip)
     */
    public function install(): array
    {
        $pluginKey = trim((string)$_POST['plugin_key']);
        $type = (int)$_POST['type'];

        if (!preg_match('/^[A-Za-z0-9_]+$/', $pluginKey)) {
            throw new JSONException("插件名称格式不合法");
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new JSONException("请上传插件zip包");
        }

        $tmpFile = $_FILES['file']['tmp_name'];
        $ext = strtolower(pathinfo((string)$_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            throw new JSONException("只允许上传 .zip 格式的插件包");
        }

        $destDir = BASE_PATH . "/kernel/Install/OS/";
        if (!is_dir($destDir)) {
            mkdir($destDir, 0777, true);
        }
        $destPath = $destDir . md5((string)time()) . ".zip";
        if (!move_uploaded_file($tmpFile, $destPath)) {
            throw new JSONException("文件上传失败");
        }

        $this->app->installPlugin($pluginKey, $type, $destPath);
        ManageLog::log($this->getManage(), "本地安装了插件({$pluginKey})");
        return $this->json(200, "安装完成");
    }

    /**
     * 卸载插件
     * POST: plugin_key, type
     */
    public function uninstall(): array
    {
        $pluginKey = (string)$_POST['plugin_key'];
        $type = (int)$_POST['type'];

        if ($type == 0) {
            _plugin_stop($pluginKey);
        }

        $this->app->uninstallPlugin($pluginKey, $type);
        ManageLog::log($this->getManage(), "卸载了插件({$pluginKey})");
        return $this->json(200, "卸载完成");
    }
}
