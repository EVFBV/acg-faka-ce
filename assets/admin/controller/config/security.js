!function () {

    // 根据所选验证类型显示/隐藏对应的配置项
    function _ToggleProvider() {
        const render = () => {
            const type = $('select[name=captcha_type]').val();
            $('.provider').hide();
            $('.provider-' + type).show();
        };

        // select2 变更事件
        $('select[name=captcha_type]').on('change', render);
        render();
    }

    function _Save() {
        $('.save-data').click(function () {
            util.post("/admin/api/config/security", util.arrayToObject($("#data-form").serializeArray()), res => {
                layer.msg(res.msg);
            });
        });
    }

    _ToggleProvider();
    _Save();
}();
