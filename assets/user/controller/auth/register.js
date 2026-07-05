!function () {
    const behavior = () => window.BehaviorCaptcha && BehaviorCaptcha.isBehavior();

    //挂载行为验证组件(未启用时自动跳过)
    if (window.BehaviorCaptcha) {
        BehaviorCaptcha.mount({
            container: '.behavior-captcha',
            form: '.needs-validation'
        });
    }

    function register() {
        const formData = new FormData($('.needs-validation')[0]);
        const data = Object.fromEntries(formData.entries());
        util.post("/user/api/authentication/register", data, res => {
            window.location.href = "/";
            message.success(res.msg);
        });
    }

    $(`.needs-validation`).on("submit", function (e) {
        e.preventDefault();
        if (behavior()) {
            BehaviorCaptcha.ensure().then(() => {
                register();
            }).catch(err => {
                message.error(typeof err === "string" ? err : "请完成人机验证");
            });
            return;
        }
        register();
    });


    //发送手机验证码(行为验证模式下走行为验证,否则弹出图形验证码)
    $(`.send-phone-captcha`).click(function () {
        const btn = this;
        if (behavior()) {
            BehaviorCaptcha.ensure().then(tokens => {
                util.post("/user/api/authentication/phoneRegisterCaptcha", Object.assign({
                    phone: $('input[name=phone]').val()
                }, tokens), res => {
                    util.countDown(btn, 60);
                    message.success("验证码发送成功");
                });
            }).catch(err => {
                message.error(typeof err === "string" ? err : "请完成人机验证");
            });
            return;
        }
        message.prompt({
            title: '人机验证',
            width: 420,
            html: `<img src="/user/captcha/image?action=phoneRegisterCaptcha" onclick="this.src='/user/captcha/image?action=phoneRegisterCaptcha&t=' + new Date().getTime()"  class="prompt-image-code" alt="更换验证码">`,
            inputAttributes: {
                onpaste: 'return false',
                oncopy: 'return false'
            },
            confirmButtonText: `继续操作`,
            inputValidator: function (value) {
                return (!value && "请输入验证码");
            }
        }).then(res => {
            if (res.isConfirmed === true) {
                util.post("/user/api/authentication/phoneRegisterCaptcha", {
                    captcha: res.value,
                    phone: $('input[name=phone]').val()
                }, res => {
                    util.countDown(btn, 60);
                    message.success("验证码发送成功");
                });
            }
        });
    });


    //发送邮箱验证码(行为验证模式下走行为验证,否则弹出图形验证码)
    $(`.send-email-code`).click(function () {
        const btn = this;
        if (behavior()) {
            BehaviorCaptcha.ensure().then(tokens => {
                util.post("/user/api/authentication/emailRegisterCaptcha", Object.assign({
                    email: $('input[name=email]').val()
                }, tokens), res => {
                    util.countDown(btn, 60);
                    message.success("验证码发送成功");
                });
            }).catch(err => {
                message.error(typeof err === "string" ? err : "请完成人机验证");
            });
            return;
        }
        message.prompt({
            title: '人机验证',
            width: 420,
            html: `<img src="/user/captcha/image?action=emailRegisterCaptcha" onclick="this.src='/user/captcha/image?action=emailRegisterCaptcha&t=' + new Date().getTime()"  class="prompt-image-code" alt="更换验证码">`,
            inputAttributes: {
                onpaste: 'return false',
                oncopy: 'return false'
            },
            confirmButtonText: `继续操作`,
            inputValidator: function (value) {
                return (!value && "请输入验证码");
            }
        }).then(res => {
            if (res.isConfirmed === true) {
                util.post("/user/api/authentication/emailRegisterCaptcha", {
                    captcha: res.value,
                    email: $('input[name=email]').val()
                }, res => {
                    util.countDown(btn, 60);
                    message.success("验证码发送成功");
                });
            }
        });
    });
}();
