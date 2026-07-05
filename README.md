# 萌次元CE（acg-faka Community Edition）

> 基于 [lizhipay/acg-faka](https://github.com/lizhipay/acg-faka)（MIT）二次开发的社区版本
> **本版本与原项目官方团队无任何关联，非官方发布。**

## 主要改动

- 移除插件云端商店与在线购买，改为本地上传安装 `.zip` 包
- 移除云端用户验证与账号体系，完全本地化运行
- 移除导航栏应用商店入口、节点选择、版本检查、官方公告
- 内置4个常用支付插件：支付宝当面付、微信支付、易支付、USDT
- 修复多处因移除云端依赖导致的崩溃问题
- 品牌标识统一为「萌次元CE」

## 环境要求

| 组件 | 版本 |
|---|---|
| PHP | >= 8.0 |
| MySQL | 5.7 / 8.0 |
| Redis | 7+ |
| 扩展 | openssl pdo_mysql mbstring gd zip curl |

## 快速部署

```bash
# 1. 克隆仓库
git clone https://github.com/EVFBV/acg-faka-ce.git
cd acg-faka-ce

# 2. 安装依赖
composer install --no-dev

# 3. 配置 Web 服务器伪静态（见下方）
# 4. 浏览器访问，进入安装向导
```

### Nginx 伪静态

```nginx
location / {
    try_files $uri $uri/ /index.php?s=$uri&$query_string;
}
```

### Apache .htaccess

项目根目录已包含 `.htaccess`，开启 `mod_rewrite` 即可。

## 内置支付插件

| 插件 | 说明 |
|---|---|
| 支付宝当面付 | 官方 RSA2，扫码支付 |
| 微信扫码支付 | APIv3 Native |
| 易支付(Epay) | 聚合跳转，支持支付宝/微信/QQ |
| USDT | TRC20/ERC20，展示收款二维码 |

## 本地安装插件

管理后台 → 插件管理 → 「本地安装插件」→ 上传 `.zip` 包即可，无需连接云端。

## License

MIT — 原项目版权 © 2021 lizhipay，CE 版改动遵循同一协议。
