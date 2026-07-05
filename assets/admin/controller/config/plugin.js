!function () {
    let table, _LogPid;

    // 本地上传安装插件弹窗
    function _UploadInstall() {
        component.popup({
            submit: false,
            tab: [
                {
                    name: '<i class="fa-duotone fa-regular fa-cloud-arrow-up"></i> 本地安装插件',
                    form: [
                        {
                            title: false,
                            name: "upload_plugin",
                            type: "custom",
                            complete: (form, dom) => {
                                dom.html(`<div style="padding:10px 0;">
                  <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <p class="mb-0"><i class="fa-duotone fa-regular fa-circle-info me-2"></i>
                    上传插件 zip 包到本地安装。zip 包内需包含插件目录结构（如 Config/Info.php 等）。
                    </p>
                  </div>
                  <div class="mb-4">
                    <label class="form-label">插件目录名（英文）</label>
                    <input type="text" id="upload-plugin-key" class="form-control" placeholder="例: MyPayPlugin">
                  </div>
                  <div class="mb-4">
                    <label class="form-label">插件类型</label>
                    <select id="upload-plugin-type" class="form-select">
                      <option value="0">通用插件</option>
                      <option value="1">支付插件</option>
                      <option value="2">网站主题</option>
                    </select>
                  </div>
                  <div class="mb-4">
                    <label class="form-label">插件 zip 包</label>
                    <input type="file" id="upload-plugin-file" class="form-control" accept=".zip">
                  </div>
                  <div class="d-grid">
                    <button type="button" class="btn btn-primary btn-do-upload">
                      <i class="fa-duotone fa-regular fa-cloud-arrow-up"></i> 立即安装
                    </button>
                  </div>
                </div>`);

                                dom.find('.btn-do-upload').click(() => {
                                    const key = $('#upload-plugin-key').val().trim();
                                    const type = $('#upload-plugin-type').val();
                                    const fileInput = document.getElementById('upload-plugin-file');

                                    if (!key) { message.error("请填写插件目录名"); return; }
                                    if (!/^[A-Za-z0-9_]+$/.test(key)) { message.error("插件目录名只能包含英文字母、数字和下划线"); return; }
                                    if (!fileInput.files || fileInput.files.length === 0) { message.error("请选择插件 zip 包"); return; }

                                    const formData = new FormData();
                                    formData.append('plugin_key', key);
                                    formData.append('type', type);
                                    formData.append('file', fileInput.files[0]);

                                    const loadIdx = layer.load(2, {shade: ['0.3', '#fff']});
                                    $.ajax({
                                        url: '/admin/api/app/install',
                                        type: 'POST',
                                        data: formData,
                                        processData: false,
                                        contentType: false,
                                        success: res => {
                                            layer.close(loadIdx);
                                            if (res.code === 200) {
                                                message.success("安装成功");
                                                layer.closeAll();
                                                table.refresh();
                                            } else {
                                                message.error(res.msg || "安装失败");
                                            }
                                        },
                                        error: () => {
                                            layer.close(loadIdx);
                                            message.error("请求失败，请检查网络");
                                        }
                                    });
                                });
                            }
                        }
                    ]
                }
            ],
            submit: false,
            maxmin: false,
            autoPosition: true,
            width: "520px"
        });
    }

    const modal = (title, assign = {}) => {
        let submit = [];
        if (typeof assign.PLUGIN_SUBMIT === "object") {
            submit = [{ name: title, form: assign.PLUGIN_SUBMIT }];
        } else if (typeof assign.PLUGIN_SUBMIT === "string" && assign.PLUGIN_SUBMIT.trim() != "") {
            submit = eval(assign.PLUGIN_SUBMIT);
        }

        component.popup({
            submit: '/admin/api/plugin/setConfig?id=' + assign.id,
            tab: submit,
            assign: assign?.PLUGIN_CONFIG ?? [],
            autoPosition: true,
            height: "auto",
            width: "680px",
            done: () => { table.refresh(); }
        });
    }

    table = new Table("/admin/api/plugin/getPlugins", "#plugin-table");
    table.setColumns([
        {checkbox: true},
        {
            field: 'plugin_name', title: '插件名称', formatter: function (val, item) {
                return `<span class="table-item"><img src="${item?.icon}" class="table-item-icon"><span class="table-item-name">${item?.NAME}</span></span>`;
            }
        },
        {
            field: 'status', title: '状态', formatter: function (val, item) {
                if (item.PLUGIN_CONFIG && item.PLUGIN_CONFIG.STATUS == 1) {
                    return '<span class="badge badge-light-success plugin-state" data-id="' + item.id + '">运行中</span>';
                }
                return '<span class="badge badge-light-danger plugin-state" data-id="' + item.id + '">未启用</span>';
            }
        },
        {
            field: 'operation', title: '控制', class: "nowrap", type: 'button', buttons: [
                {
                    icon: 'fa-duotone fa-regular fa-circle-stop',
                    class: "text-danger",
                    title: "停用",
                    show: item => item.PLUGIN_CONFIG && item.PLUGIN_CONFIG.STATUS == 1,
                    click: (event, value, row) => {
                        util.post("/admin/api/plugin/setConfig", {id: row.id, STATUS: 0}, res => {
                            table.refresh();
                            $('.plugin-state[data-id=' + row.id + ']').removeClass("badge-light-success").addClass("badge-light-danger").html("已停止");
                            layer.msg(res.msg);
                        });
                    }
                },
                {
                    icon: 'fa-duotone fa-regular fa-circle-play',
                    class: 'text-success',
                    title: '启用',
                    show: item => item.PLUGIN_CONFIG && (item.PLUGIN_CONFIG?.STATUS == 0 || !item.PLUGIN_CONFIG?.STATUS),
                    click: (event, value, row) => {
                        util.post("/admin/api/plugin/setConfig", {id: row.id, STATUS: 1}, res => {
                            table.refresh();
                            $('.plugin-state[data-id=' + row.id + ']').removeClass("badge-light-danger").addClass("badge-light-success").html("已启动");
                            layer.msg(res.msg);
                        });
                    }
                },
                {
                    icon: 'fa-duotone fa-regular fa-gear',
                    class: 'text-primary',
                    title: '配置',
                    show: item => item.hasOwnProperty('PLUGIN_SUBMIT') && item.PLUGIN_SUBMIT.length > 0,
                    click: (event, value, row) => {
                        modal(util.icon("fa-duotone fa-regular fa-gear") + row.NAME, row);
                    }
                },
                {
                    icon: 'fa-duotone fa-regular fa-bug',
                    title: '日志',
                    click: (event, value, row) => {
                        let mapItem = row, logPid = _LogPid = util.generateRandStr(16);
                        util.post('/admin/api/plugin/getPluginLog', {handle: mapItem.id}, res => {
                            layer.open({
                                type: 1, shade: 0.4, shadeClose: true,
                                title: '<i class="fa-duotone fa-regular fa-ban-bug"></i> 日志',
                                btn: ["清空日志", "关闭"],
                                content: '<textarea class="log-textarea" style="width:100%;height:100%;border:none;color:grey;padding:5px;">' + res.data.log + '</textarea>',
                                area: util.isPc() ? ["860px", "660px"] : ["100%", "100%"],
                                maxmin: true,
                                yes: () => {
                                    util.post('/admin/api/plugin/ClearPluginLog', {handle: mapItem.id}, () => { layer.msg("日志已清空"); });
                                },
                                success: () => {
                                    util.timer(() => new Promise(resolve => {
                                        if (_LogPid !== logPid) { resolve(false); return; }
                                        util.post({url: '/admin/api/plugin/getPluginLog', data: {handle: mapItem.id}, loader: false,
                                            done: r => {
                                                if (r.data.log != $('.log-textarea').html()) { $('.log-textarea').html(r.data.log); }
                                                resolve(true);
                                            }
                                        });
                                    }), 1500);
                                },
                                end: () => { _LogPid = null; }
                            });
                        });
                    }
                },
                {
                    icon: 'fa-duotone fa-regular fa-trash-can text-danger',
                    title: '卸载',
                    click: (event, value, row) => {
                        message.ask(`你想要卸载<b style="color:mediumvioletred;">${row.NAME}</b>吗，该操作会清空插件所有数据，且无法恢复！`, () => {
                            util.post('/admin/api/app/uninstall', {plugin_key: row.id, type: 0}, () => {
                                message.success("卸载成功");
                                table.refresh();
                            });
                        });
                    }
                }
            ]
        },
        {
            field: 'wiki', title: 'Wiki', formatter: function (val, item) {
                if (!item.wiki) return '-';
                return '<a class="badge badge-light-primary" href="' + item.wiki + '" target="_blank">文档</a>';
            }
        },
        {field: 'VERSION', title: '版本号', formatter: (val, item) => '<span class="badge badge-light">' + item.VERSION + '</span>'},
        {field: 'DESCRIPTION', title: '简介', class: "break-spaces"},
        {
            field: 'PLUGIN_CONFIG.top', title: 'TOP', class: "nowrap", type: "switch", text: "置顶|无", reload: true,
            change: (state, row) => {
                util.post('/admin/api/plugin/setConfig?id=' + row.id, {top: state}, () => {
                    table.$table.bootstrapTable('refresh', {silent: true, pageNumber: 1});
                });
            }
        },
        {
            field: 'author', title: '作者', formatter: function (val, item) {
                if (item.AUTHOR == "#" || !item.AUTHOR) return '-';
                return '<span class="badge badge-light"><i class="fa-duotone fa-regular fa-circle-user"></i> ' + item.AUTHOR + '</span>';
            }
        }
    ]);

    table.setSearch([{title: "搜索插件..", name: "keywords", type: "input"}]);
    table.setState("status", [{id: 0, name: "未运行"}, {id: 1, name: "正在运行"}]);
    table.disablePagination();
    table.render();

    // 本地安装按钮
    $('.btn-plugin-upload').click(() => { _UploadInstall(); });

    // 批量启动
    $('.plugin-start').click(() => {
        let plugins = table.getSelections();
        if (plugins.length == 0) { layer.msg("请至少勾选1个插件进行操作！"); return; }
        const $ins = $('.plugin-start span');
        let index = 0;
        const loadIdx = layer.load(2, {shade: ['0.3', '#fff']});
        util.timer(() => new Promise(resolve => {
            $ins.html(`正在启动 ${index}/${plugins.length}`);
            const plugin = plugins[index++];
            if (plugin && (plugin?.PLUGIN_CONFIG?.STATUS == 0 || !plugin?.PLUGIN_CONFIG?.hasOwnProperty("STATUS"))) {
                util.post({url: "/admin/api/plugin/setConfig", data: {id: plugin.id, STATUS: 1},
                    done: () => { $('.plugin-state[data-id=' + plugin.id + ']').removeClass("badge-light-danger").addClass("badge-light-success").html("已启动"); resolve(true); },
                    error: () => resolve(true), fail: () => resolve(true), loader: false});
                return;
            } else if (plugin && plugin?.PLUGIN_CONFIG?.STATUS != 0) { resolve(true); return; }
            table.refresh(); $ins.html(`启动插件`); layer.close(loadIdx); resolve(false);
        }), 300, true);
    });

    // 批量停止
    $('.plugin-stop').click(() => {
        let plugins = table.getSelections();
        if (plugins.length == 0) { layer.msg("请至少勾选1个插件进行操作！"); return; }
        const $ins = $('.plugin-stop span');
        let index = 0;
        const loadIdx = layer.load(2, {shade: ['0.3', '#fff']});
        util.timer(() => new Promise(resolve => {
            $ins.html(`正在停止 ${index}/${plugins.length}`);
            const plugin = plugins[index++];
            if (plugin && plugin?.PLUGIN_CONFIG?.STATUS == 1) {
                util.post({url: "/admin/api/plugin/setConfig", data: {id: plugin.id, STATUS: 0},
                    done: () => { $('.plugin-state[data-id=' + plugin.id + ']').removeClass("badge-light-success").addClass("badge-light-danger").html("已停止"); resolve(true); },
                    error: () => resolve(true), fail: () => resolve(true), loader: false});
                return;
            } else if (plugin && plugin?.PLUGIN_CONFIG?.STATUS != 1) { resolve(true); return; }
            table.refresh(); $ins.html(`停止插件`); layer.close(loadIdx); resolve(false);
        }), 300, true);
    });
}();
