!function () {
    let goto = decodeURIComponent(util.getParam("goto"));

    if (goto == "null") {
        goto = "/";
    }

    //挂载行为验证组件(未启用时自动跳过)
    if (window.BehaviorCaptcha) {
        BehaviorCaptcha.mount({
            container: '.behavior-captcha',
            form: '.needs-validation'
        });
    }

    function submit() {
        const formData = new FormData($('.needs-validation')[0]);
        const data = Object.fromEntries(formData.entries());
        util.post("/user/api/authentication/login", data, res => {
            window.location.href = goto;
            message.success(res.msg);
        });
    }

    $(`.needs-validation`).on("submit", function (e) {
        e.preventDefault();
        if (window.BehaviorCaptcha && BehaviorCaptcha.isBehavior()) {
            BehaviorCaptcha.ensure().then(() => {
                submit();
            }).catch(err => {
                message.error(typeof err === "string" ? err : "请完成人机验证");
            });
            return;
        }
        submit();
    });
}();
