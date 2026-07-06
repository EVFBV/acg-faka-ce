<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Base\API\User;
use App\Service\App;
use App\Util\Client;
use App\Util\Opcache;
use App\Util\Str;
use App\Util\Validation;
use Kernel\Annotation\Inject;
use Kernel\Exception\JSONException;
use Kernel\Util\SQL;
use Kernel\Util\View;

class Install extends User
{

    #[Inject]
    private App $app;

    /**
     * 伪静态探测
     */
    public function rewrite(): array
    {
        return $this->json(200, "success");
    }

    /**
     * 安装引导页
     */
    public function step(): string
    {
        if (file_exists(BASE_PATH . '/kernel/Install/Lock')) {
            Client::redirect("/", "どうして?", 3);
        }
        $data = [];
        $data['version'] = config("app")['version'];
        $data['php_version'] = phpversion();

        $data['ext']['gd']        = extension_loaded("gd");
        $data['ext']['curl']      = extension_loaded("curl");
        $data['ext']['pdo']       = extension_loaded("PDO");
        $data['ext']['pdo_mysql'] = extension_loaded("pdo_mysql");
        $data['ext']['date']      = extension_loaded("date");
        $data['ext']['json']      = extension_loaded("json");
        $data['ext']['session']   = extension_loaded("session");
        $data['ext']['zip']       = extension_loaded("zip");

        $data['install'] = true;
        if ($data['php_version'] < 8) {
            $data['install'] = false;
        } else {
            foreach ($data['ext'] as $ext) {
                if (!$ext) {
                    $data['install'] = false;
                }
            }
        }

        return View::render("Install.html", $data);
    }

    /**
     * 执行安装
     */
    public function submit(): array
    {
        if (file_exists(BASE_PATH . '/kernel/Install/Lock')) {
            throw new JSONException("您已经安装过了，如果想重新安装，请删除 /kernel/Install/Lock 文件后重试！");
        }

        $map = $_POST;
        foreach ($map as $k => $v) {
            $map[$k] = trim((string)$v);
        }

        $host         = $map['host']         ?: 'localhost';
        $database     = $map['database']     ?? '';
        $username     = $map['username']     ?? '';
        $password     = $map['password']     ?? '';
        $prefix       = $map['prefix']       ?: 'acg_';
        $email        = $map['email']        ?? '';
        $nickname     = $map['nickname']     ?? '';
        $loginPwd     = $map['login_password'] ?? '';
        $installMode  = $map['install_mode'] ?? 'fresh'; // fresh | update

        // 全新安装：必须验证管理员信息
        if ($installMode === 'fresh') {
            if (!Validation::email($email)) {
                throw new JSONException("管理员邮箱格式不正确");
            }
            if (!Validation::password($loginPwd)) {
                throw new JSONException("您设置的登录密码过于简单");
            }
            $this->dropAllTables($host, $database, $username, $password);
        }

        $sqlFile = BASE_PATH . '/kernel/Install/Install.sql';
        // 统一换行符，避免 Windows \r\n 导致正则失配
        $sqlSrc  = str_replace("\r\n", "\n", (string)file_get_contents($sqlFile));

        if ($installMode === 'fresh') {
            // 全新安装：替换管理员占位符
            $salt = Str::generateRandStr(32);
            $pw   = Str::generatePassword($loginPwd, $salt);
            $sqlSrc = str_replace('__MANAGE_EMAIL__',    $email,    $sqlSrc);
            $sqlSrc = str_replace('__MANAGE_PASSWORD__', $pw,       $sqlSrc);
            $sqlSrc = str_replace('__MANAGE_SALT__',     $salt,     $sqlSrc);
            $sqlSrc = str_replace('__MANAGE_NICKNAME__', $nickname, $sqlSrc);
        } else {
            // 升级模式：
            // 1. 移除所有 DROP TABLE 语句（防止清空现有表）
            $sqlSrc = preg_replace('/DROP TABLE IF EXISTS[^\n]+;\n/i', '', $sqlSrc);
            // 2. CREATE TABLE → CREATE TABLE IF NOT EXISTS（不覆盖已有表）
            $sqlSrc = preg_replace('/CREATE TABLE\s+`/i', 'CREATE TABLE IF NOT EXISTS `', $sqlSrc);
            // 3. 移除 manage 表的 INSERT 行（完全保留现有管理员账号，不插入也不覆盖）
            $sqlSrc = preg_replace('/^INSERT\s+(?:IGNORE\s+)?INTO\s+`?__PREFIX__manage`?[^\n]+\n/im', '', $sqlSrc);
            // 4. 其余 INSERT 改为 INSERT IGNORE（初始化配置/基础数据不覆盖已有记录）
            $sqlSrc = preg_replace('/^INSERT INTO\s+/im', 'INSERT IGNORE INTO ', $sqlSrc);
            // 移除管理员占位符（防止含占位符的语句残留导致SQL语法错误）
            $sqlSrc = str_replace(['__MANAGE_EMAIL__', '__MANAGE_PASSWORD__', '__MANAGE_SALT__', '__MANAGE_NICKNAME__'], ['', '', '', ''], $sqlSrc);
        }

        $tmpFile = $sqlFile . ".tmp";
        if (file_put_contents($tmpFile, $sqlSrc) === false) {
            throw new JSONException("没有写入权限，请检查目录权限");
        }

        SQL::import($tmpFile, $host, $database, $username, $password, $prefix);

        setConfig([
            'driver'    => 'mysql',
            'host'      => $host,
            'database'  => $database,
            'username'  => $username,
            'password'  => $password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => $prefix,
        ], BASE_PATH . "/config/database.php");

        Opcache::invalidate(BASE_PATH . "/config/database.php");
        unlink($tmpFile);
        file_put_contents(BASE_PATH . '/kernel/Install/Lock', "");

        try {
            $this->app->install();
        } catch (\Exception|\Error $e) {
        }

        $modeLabel = $installMode === 'fresh' ? '全新安装' : '升级/更新';
        return $this->json(200, "{$modeLabel}完成");
    }

    /**
     * 全新安装前清空数据库所有表（告知用户后执行）
     */
    private function dropAllTables(string $host, string $db, string $user, string $pass): void
    {
        try {
            $pdo = new \PDO(
                "mysql:host={$host};dbname={$db};charset=utf8mb4",
                $user,
                $pass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (\PDOException $e) {
            throw new JSONException("清空数据库失败：" . $e->getMessage() . "\n请检查数据库连接信息是否正确。");
        }
    }
}
