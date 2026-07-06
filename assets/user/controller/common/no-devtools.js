/**
 * 禁用前台开发者工具(F12调试器)
 * 策略1: 拦截快捷键(F12/Ctrl+Shift+I/J/C/右键)
 * 策略2: devtools 打开时 console.log 对象会触发 getter，利用此特性检测并跳转
 */
!function () {
    "use strict";

    // ── 策略1: 拦截快捷键及右键菜单 ──────────────────────────────────────
    document.addEventListener("keydown", function (e) {
        // F12
        if (e.key === "F12" || e.keyCode === 123) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        // Ctrl+Shift+I / Ctrl+Shift+J / Ctrl+Shift+C
        if (e.ctrlKey && e.shiftKey && /^[ijcIJC]$/.test(e.key)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        // Ctrl+U (查看源代码)  Ctrl+P (打印)
        if (e.ctrlKey && /^[upUP]$/.test(e.key)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
    }, true);

    document.addEventListener("contextmenu", function (e) {
        e.preventDefault();
        return false;
    }, true);

    // ── 策略2: 利用 console 对象 getter 检测 devtools 是否已打开 ──────────
    // 原理: devtools 打开后，浏览器会在后台调用被 console.log 的对象的 getter，
    //        通过 Object.defineProperty 设置 getter 并在其中标记即可检测。
    var _devtoolsOpen = false;
    var _redirected   = false;

    var _img = new Image();
    Object.defineProperty(_img, "id", {
        get: function () {
            _devtoolsOpen = true;
        }
    });

    var _check = function () {
        _devtoolsOpen = false;
        console.log(_img);      // 触发 getter
        console.clear();        // 清空 console，避免积累

        if (_devtoolsOpen && !_redirected) {
            _redirected = true;
            document.body.innerHTML =
                '<div style="display:flex;align-items:center;justify-content:center;height:100vh;' +
                'font-family:sans-serif;background:#f5f6fa;">' +
                '<div style="text-align:center;padding:40px;background:#fff;border-radius:12px;' +
                'box-shadow:0 4px 24px rgba(0,0,0,.1);">' +
                '<h2 style="color:#e74c3c;">⚠️ 访问受限</h2>' +
                '<p style="color:#666;">请关闭开发者工具后刷新页面。</p>' +
                '<button onclick="location.reload()" style="margin-top:16px;padding:8px 24px;' +
                'background:#e74c3c;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;">' +
                '刷新页面</button></div></div>';
        }
    };

    // 每隔 1 秒检测一次
    setInterval(_check, 1000);
}();
