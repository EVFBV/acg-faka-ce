/**
 * 行为验证前端统一模块 (极验 / Cloudflare Turnstile / 阿里云验证码2.0)
 *
 * 用法:
 *   BehaviorCaptcha.mount({
 *       container: '.behavior-captcha',   // 组件容器(存在时内联渲染)
 *       form: '.needs-validation',        // 关联表单,token 将写入表单内隐藏域
 *       onReady: fn                       // 组件就绪回调(可选)
 *   });
 *
 * 提交前调用 BehaviorCaptcha.ensure().then(tokens => {...}) 获取验证参数,
 * 或直接依赖写入表单的隐藏域(Turnstile / 内联极验)。
 *
 * 配置来源: 后端注入的全局变量 _captcha_provider(JSON)
 *   { "type":"image|geetest|turnstile|aliyun", ...公钥 }
 *
 * 新增服务商: 在 DRIVERS 中新增一个实现 {load, render, getToken} 的驱动即可。
 */
window.BehaviorCaptcha = (function () {
    "use strict";

    function config() {
        let cfg = (typeof getVar === "function" && getVar("_captcha_provider")) || window._captcha_provider || {type: "image"};
        if (typeof cfg === "string") {
            try {
                cfg = JSON.parse(cfg);
            } catch (e) {
                cfg = {type: "image"};
            }
        }
        return cfg || {type: "image"};
    }

    function type() {
        return (config().type || "image").toLowerCase();
    }

    function isBehavior() {
        return type() !== "image";
    }

    //动态加载脚本(去重)
    const _loaded = {};

    function loadScript(src) {
        if (_loaded[src]) {
            return _loaded[src];
        }
        _loaded[src] = new Promise((resolve, reject) => {
            const s = document.createElement("script");
            s.src = src;
            s.async = true;
            s.onload = () => resolve();
            s.onerror = () => reject(new Error("加载验证组件失败: " + src));
            document.head.appendChild(s);
        });
        return _loaded[src];
    }

    //向表单写入隐藏域
    function setHidden(form, name, value) {
        if (!form) return;
        let $f = $(form);
        let input = $f.find(`input[name="${name}"]`);
        if (input.length === 0) {
            input = $(`<input type="hidden" name="${name}">`);
            $f.append(input);
        }
        input.val(value);
    }

    /* -------------------- 驱动实现 -------------------- */
    const DRIVERS = {
        //极验行为验证 v4
        geetest: {
            _obj: null,
            load() {
                return loadScript("https://static.geetest.com/v4/gt4.js");
            },
            render(ctx) {
                const cfg = config();
                const self = this;
                return this.load().then(() => new Promise((resolve) => {
                    initGeetest4({
                        captchaId: cfg.captcha_id,
                        product: "float",
                        language: "zho"
                    }, function (obj) {
                        self._obj = obj;
                        obj.appendTo(ctx.container);
                        obj.onSuccess(function () {
                            const result = obj.getValidate() || {};
                            setHidden(ctx.form, "lot_number", result.lot_number || "");
                            setHidden(ctx.form, "captcha_output", result.captcha_output || "");
                            setHidden(ctx.form, "pass_token", result.pass_token || "");
                            setHidden(ctx.form, "gen_time", result.gen_time || "");
                        });
                        ctx.onReady && ctx.onReady();
                        resolve();
                    });
                }));
            },
            //返回 Promise,确保已完成验证
            ensure(ctx) {
                const obj = this._obj;
                if (!obj) return Promise.reject("验证组件未就绪");
                const result = obj.getValidate();
                if (result && result.captcha_output) {
                    return Promise.resolve({
                        lot_number: result.lot_number,
                        captcha_output: result.captcha_output,
                        pass_token: result.pass_token,
                        gen_time: result.gen_time
                    });
                }
                //未完成则弹出验证
                return new Promise((resolve, reject) => {
                    obj.onSuccess(function () {
                        const r = obj.getValidate() || {};
                        setHidden(ctx.form, "lot_number", r.lot_number || "");
                        setHidden(ctx.form, "captcha_output", r.captcha_output || "");
                        setHidden(ctx.form, "pass_token", r.pass_token || "");
                        setHidden(ctx.form, "gen_time", r.gen_time || "");
                        resolve({
                            lot_number: r.lot_number,
                            captcha_output: r.captcha_output,
                            pass_token: r.pass_token,
                            gen_time: r.gen_time
                        });
                    });
                    obj.showCaptcha && obj.showCaptcha();
                    setTimeout(() => reject("请完成人机验证"), 60000);
                });
            }
        },

        //Cloudflare Turnstile
        turnstile: {
            load() {
                return loadScript("https://challenges.cloudflare.com/turnstile/v0/api.js");
            },
            render(ctx) {
                const cfg = config();
                return this.load().then(() => {
                    const el = document.createElement("div");
                    el.className = "cf-turnstile";
                    el.setAttribute("data-sitekey", cfg.site_key);
                    el.setAttribute("data-theme", "light");
                    //Turnstile 会在容器内自动生成 name="cf-turnstile-response" 隐藏域
                    $(ctx.container).empty().append(el);
                    ctx.onReady && ctx.onReady();
                });
            },
            ensure(ctx) {
                const token = $(ctx.container).find('[name="cf-turnstile-response"]').val();
                if (token) {
                    setHidden(ctx.form, "cf-turnstile-response", token);
                    return Promise.resolve({"cf-turnstile-response": token});
                }
                return Promise.reject("请完成人机验证");
            }
        },

        //阿里云验证码 2.0
        aliyun: {
            _captcha: null,
            _verifyResolve: null,
            load() {
                return loadScript("https://o.alicdn.com/captcha-frontend/aliyunCaptcha/AliyunCaptcha.js");
            },
            render(ctx) {
                const cfg = config();
                const self = this;
                return this.load().then(() => new Promise((resolve) => {
                    //为阿里云组件准备触发按钮容器
                    const btnId = "aliyun-captcha-btn";
                    const elId = "aliyun-captcha-element";
                    $(ctx.container).empty().append(
                        `<div id="${elId}"></div><button type="button" id="${btnId}" class="btn btn-outline-primary w-100">点击完成人机验证</button>`
                    );
                    initAliyunCaptcha({
                        SceneId: cfg.scene_id,
                        prefix: cfg.prefix,
                        mode: "popup",
                        element: "#" + elId,
                        button: "#" + btnId,
                        captchaVerifyCallback: function (captchaVerifyParam) {
                            setHidden(ctx.form, "captcha_verify_param", captchaVerifyParam);
                            self._token = captchaVerifyParam;
                            if (self._verifyResolve) {
                                self._verifyResolve({captcha_verify_param: captchaVerifyParam});
                                self._verifyResolve = null;
                            }
                            //返回给阿里云组件,表示业务侧校验已收集(实际校验在后端)
                            return {captchaResult: true, bizResult: true};
                        },
                        onBizResultCallback: function () {
                        },
                        getInstance: function (instance) {
                            self._captcha = instance;
                        },
                        slideStyle: {width: 320, height: 40},
                        language: "cn"
                    });
                    ctx.onReady && ctx.onReady();
                    resolve();
                }));
            },
            ensure(ctx) {
                if (this._token) {
                    setHidden(ctx.form, "captcha_verify_param", this._token);
                    return Promise.resolve({captcha_verify_param: this._token});
                }
                const self = this;
                return new Promise((resolve, reject) => {
                    self._verifyResolve = resolve;
                    $("#aliyun-captcha-btn").trigger("click");
                    setTimeout(() => reject("请完成人机验证"), 60000);
                });
            }
        }
    };

    function driver() {
        return DRIVERS[type()] || null;
    }

    let _ctx = null;

    return {
        isBehavior: isBehavior,
        type: type,
        config: config,

        /**
         * 挂载验证组件
         * @param {{container:string, form:string, onReady?:Function}} opt
         */
        mount(opt) {
            if (!isBehavior()) {
                return Promise.resolve();
            }
            const d = driver();
            if (!d) {
                return Promise.resolve();
            }
            _ctx = {
                container: opt.container,
                form: opt.form,
                onReady: opt.onReady
            };
            //隐藏原生图形验证码块
            $(".behavior-hide-when-active").hide();
            return d.render(_ctx).catch(err => {
                console.error(err);
            });
        },

        /**
         * 确保已完成验证并返回 token 参数
         * @returns {Promise<object>}
         */
        ensure() {
            if (!isBehavior()) {
                return Promise.resolve({});
            }
            const d = driver();
            if (!d || !_ctx) {
                return Promise.resolve({});
            }
            return d.ensure(_ctx);
        }
    };
})();
