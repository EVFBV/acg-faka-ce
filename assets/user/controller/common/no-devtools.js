/**
 * 禁用前台开发者工具(F12调试器)
 * 策略1: 拦截快捷键(F12/Ctrl+Shift+I/J/C/右键)
 * 策略2: 双重检测(Image getter + 窗口尺寸差异)，检测到立即全屏白色遮罩
 *         关闭 devtools 后遮罩自动消失，轮询间隔 300ms
 * 注意: 仅前台用户页加载，admin 后台不受影响。
 */
!function () {
    "use strict";

    // ── 遮罩层 ──────────────────────────────────────────────────────────
    var _overlay = document.createElement('div');
    _overlay.style.cssText =
        'display:none;position:fixed;top:0;left:0;width:100%;height:100%;' +
        'background:#ffffff;z-index:2147483647;box-sizing:border-box;';
    _overlay.innerHTML =
        '<div style="display:flex;align-items:center;justify-content:center;' +
        'height:100%;font-family:sans-serif;">' +
        '<div style="text-align:center;padding:40px;">' +
        '<div style="font-size:48px;margin-bottom:16px;">🔒</div>' +
        '<h2 style="color:#e74c3c;margin:0 0 12px;">访问受限</h2>' +
        '<p style="color:#888;font-size:14px;margin:0;">请关闭开发者工具后继续访问。</p>' +
        '</div></div>';

    function _mountOverlay() {
        if (document.body && !document.getElementById('__ndt_overlay__')) {
            _overlay.id = '__ndt_overlay__';
            document.body.appendChild(_overlay);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', _mountOverlay);
    } else {
        _mountOverlay();
    }

    // ── 快捷键及右键拦截 ─────────────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F12' || e.keyCode === 123) {
            e.preventDefault(); e.stopImmediatePropagation(); return false;
        }
        if (e.ctrlKey && e.shiftKey && /^[ijcIJC]$/.test(e.key)) {
            e.preventDefault(); e.stopImmediatePropagation(); return false;
        }
        if (e.ctrlKey && /^[upUP]$/.test(e.key)) {
            e.preventDefault(); e.stopImmediatePropagation(); return false;
        }
    }, true);

    document.addEventListener('contextmenu', function (e) {
        e.preventDefault(); return false;
    }, true);

    // ── 检测方法1: Image getter（Chrome/Edge 可靠触发）──────────────────
    var _img = new Image();
    var _getter1 = false;
    Object.defineProperty(_img, 'id', {
        get: function () { _getter1 = true; }
    });

    // ── 检测方法2: 窗口尺寸差异（阈值 200px，避免工具栏误触发）──────────
    // 浏览器工具栏约 70-100px，docked devtools 通常 300px+
    var _THRESHOLD = 200;

    // ── 主检测循环 ───────────────────────────────────────────────────────
    var _open = false;

    setInterval(function () {
        // 方法1
        _getter1 = false;
        console.log(_img);
        console.clear();

        // 方法2
        var wDiff = window.outerWidth  - window.innerWidth;
        var hDiff = window.outerHeight - window.innerHeight;
        var _open2 = wDiff > _THRESHOLD || hDiff > _THRESHOLD;

        var detected = _getter1 || _open2;

        if (detected !== _open) {
            _open = detected;
            var el = document.getElementById('__ndt_overlay__');
            if (!el) { _mountOverlay(); el = document.getElementById('__ndt_overlay__'); }
            if (el) el.style.display = _open ? 'block' : 'none';
        }
    }, 300);
}();
