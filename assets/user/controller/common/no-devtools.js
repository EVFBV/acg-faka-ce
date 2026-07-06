/**
 * 禁用前台开发者工具(F12调试器)
 * 三重策略：快捷键拦截 + debugger无限循环 + 窗口尺寸差异检测
 * 仅对前台用户生效，后台管理页不加载此文件。
 */
!function () {
    "use strict";

    // 1. 拦截常见快捷键
    document.addEventListener("keydown", function (e) {
        // F12
        if (e.key === "F12" || e.keyCode === 123) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        // Ctrl+Shift+I / Ctrl+Shift+J / Ctrl+Shift+C
        if (e.ctrlKey && e.shiftKey && (e.key === "I" || e.key === "i" ||
            e.key === "J" || e.key === "j" ||
            e.key === "C" || e.key === "c")) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        // Ctrl+U (查看源代码)
        if (e.ctrlKey && (e.key === "U" || e.key === "u")) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        // Ctrl+P (打印，可能暴露布局)
        if (e.ctrlKey && (e.key === "P" || e.key === "p")) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
    }, true);

    // 2. 禁用右键菜单（防止"检查元素"）
    document.addEventListener("contextmenu", function (e) {
        e.preventDefault();
        return false;
    }, true);

    // 3. debugger 无限循环 — 调试器附加时会卡住脚本执行，给用户强烈的不便感
    //    在 Worker 中运行避免阻塞主线程 UI
    var _dbgWorkerSrc = 'setInterval(function(){debugger;},50);';
    try {
        var blob = new Blob([_dbgWorkerSrc], {type: "application/javascript"});
        var url = URL.createObjectURL(blob);
        new Worker(url);
    } catch (e) {
        // 降级：直接在页面内轮询（部分浏览器限制 Worker blob）
        setInterval(function () { debugger; }, 200);
    }

    // 4. 窗口尺寸差异检测 — 横向/纵向打开 devtools 时 window.outerWidth/Height 会变大
    var _threshold = 160;
    var _warned = false;
    var _checkDevtools = function () {
        var widthDiff  = window.outerWidth  - window.innerWidth;
        var heightDiff = window.outerHeight - window.innerHeight;
        if (widthDiff > _threshold || heightDiff > _threshold) {
            if (!_warned) {
                _warned = true;
                // 重定向到空白页，关闭 devtools 后刷新即可恢复
                document.body.innerHTML = "";
                window.location.replace("about:blank");
            }
        } else {
            _warned = false;
        }
    };
    setInterval(_checkDevtools, 1000);
}();
