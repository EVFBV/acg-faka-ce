# 萌次元CE &nbsp;·&nbsp; acg-faka Community Edition

> 基于 [lizhipay/acg-faka](https://github.com/lizhipay/acg-faka)（MIT）二次开发的完全本地化社区版本。
> **本版本与原项目官方团队无任何关联，非官方发布。**

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.0-8892bf)](https://www.php.net/)
[![Release](https://img.shields.io/github/v/release/EVFBV/acg-faka-ce)](https://github.com/EVFBV/acg-faka-ce/releases)

---

## 特性

| 模块 | 说明 |
|---|---|
| 完全本地化 | 移除云端用户验证、版本检查、官方公告，无需账号，部署即用 |
| 插件本地安装 | 管理后台上传 `.zip` 包安装插件，无需连接插件商店 |
| 4 个内置支付 | 支付宝当面付（RSA2）、微信 Native（V3）、易支付、USDT TRC20/ERC20 |
| 行为验证 | 支持 **极验 v4 / Cloudflare Turnstile / 阿里云验证码 2.0**，后台一键切换，未启用时回退数字验证码 |
| 可扩展架构 | 支付插件与验证驱动均预留接口，新增只需实现接口并注册一行 |

---

## 与上游的主要差异

- 移除插件云端商店与在线购买入口，改为本地上传安装
- 移除云端用户验证与账号绑定体系
- 移除导航栏应用商店、节点选择、版本检查等云端功能
- 修复内置支付插件三项严重缺陷：扫码支付页空白、支付回调无法完成订单、死代码根类
- 新增行为验证驱动架构（极验 / Turnstile / 阿里云）
- 品牌标识统一为「萌次元CE」

---

## 环境要求

| 组件 | 版本 |
|---|---|
| PHP | >= 8.0 |
| MySQL | 5.7 / 8.0 |
| Redis | 7+ |
| PHP 扩展 | openssl · pdo_mysql · mbstring · gd · zip · curl |

---

## 快速部署

```bash
# 1. 克隆
git clone https://github.com/EVFBV/acg-faka-ce.git
cd acg-faka-ce

# 2. 安装依赖
composer install --no-dev

# 3. 配置 Web 服务器伪静态（见下方）
# 4. 浏览器访问，按安装向导完成初始化
```

### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?s=$uri&$query_string;
}
```

### Apache

项目根目录已包含 `.htaccess`，开启 `mod_rewrite` 即可。

---

## 内置支付插件

| 插件 | 方式 | 回调验签 |
|---|---|---|
| 支付宝当面付 | RSA2 扫码 | SHA256withRSA |
| 微信扫码支付 | APIv3 Native | AES-256-GCM 解密 |
| 易支付 (Epay) | 聚合跳转（支付宝/微信/QQ） | MD5 |
| USDT | TRC20/ERC20 展示收款码 | 手动确认（链上无可信回调） |

---

## 行为验证配置

后台 **网站设置 → 安全设置** 中选择验证类型并填入对应密钥即可生效。

| 服务 | 所需密钥 |
|---|---|
| 极验 v4 | captcha_id · captcha_key |
| Cloudflare Turnstile | Site Key · Secret Key |
| 阿里云验证码 2.0 | AccessKeyId · AccessKeySecret · SceneId · Prefix |

---

## 本地安装插件

管理后台 → 插件管理 → 「本地安装插件」→ 上传 `.zip` 包，无需连接任何云端服务。

---

## Changelog

查看 [Releases](https://github.com/EVFBV/acg-faka-ce/releases) 获取完整版本历史。

---

## License

MIT — 原项目版权 © 2021 lizhipay，本 CE 版改动遵循同一协议。
