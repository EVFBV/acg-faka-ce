!function () {
    // 获取仪表板数据
    function loadDashboardData(type) {
        let loaderIndex = layer.load(2, {shade: ['0.3', '#fff']});
        $.ajax({
            type: 'post',
            url: '/admin/api/dashboard/data',
            data: {type: type},
            dataType: 'json',
            success: res => {
                layer.close(loaderIndex);
                if (res.code == 200) {
                    $('.turnover').text("￥" + res.data.turnover);
                    $('.order_num').text(res.data.order_num);
                    $('.business').text(res.data.business);
                    $('.cash_status_0').text(res.data.cash_status_0);
                    $('.cash_money_status_1').text("￥" + res.data.cash_money_status_1);
                    $('.user_register_num').text(res.data.user_register_num);
                    $('.order_profit').text("￥" + res.data.profit);
                    $('.recharge_amount').text("￥" + res.data.recharge_amount);
                    $('.divide_amount').text("￥" + res.data.divide_amount);
                    $('.rebate').text("￥" + res.data.rebate);
                    $('.cost').text("￥" + res.data.cost);
                    $('.online_amout').text("￥" + res.data.online_amout);
                }
            },
            error: () => layer.close(loaderIndex)
        });
    }

    function loadWeekStatistics() {
        $.ajax({
            type: 'get',
            url: '/admin/api/dashboard/weekStatistics',
            dataType: 'json',
            success: res => {
            if (res.code != 200) {
                layer.msg(res.msg);
                return;
            }

            let statistics = echarts.init(document.getElementById('statistics'));
            let option = {
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross',
                        label: {
                            backgroundColor: '#6a7985'
                        }
                    }
                },
                legend: {
                    data: ['盈利', '交易金额', '提现', '充值'],
                    textStyle: {
                        fontSize: 12
                    }
                },
                toolbox: {
                    feature: {
                        saveAsImage: {}
                    }
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                xAxis: [
                    {
                        type: 'category',
                        boundaryGap: false,
                        data: res.data.week,
                        axisLabel: {
                            fontSize: 10
                        }
                    }
                ],
                yAxis: [
                    {
                        type: 'value',
                        axisLabel: {
                            fontSize: 10
                        }
                    }
                ],
                series: [
                    {
                        name: '盈利',
                        type: 'line',
                        stack: 'Total',
                        label: {
                            show: true,
                            position: 'top',
                            fontSize: 10
                        },
                        areaStyle: {
                            opacity: 0.3
                        },
                        emphasis: {
                            focus: 'series'
                        },
                        data: res.data.series.profit,
                        itemStyle: {
                            color: '#3e8300'
                        }
                    },
                    {
                        name: '交易金额',
                        type: 'line',
                        stack: 'Total',
                        label: {
                            show: true,
                            position: 'top',
                            fontSize: 10
                        },
                        areaStyle: {
                            opacity: 0.3
                        },
                        emphasis: {
                            focus: 'series'
                        },
                        data: res.data.series.trade,
                        itemStyle: {
                            color: '#007bff'
                        }
                    },
                    {
                        name: '提现',
                        type: 'line',
                        stack: 'Total',
                        areaStyle: {
                            opacity: 0.3
                        },
                        emphasis: {
                            focus: 'series'
                        },
                        data: res.data.series.cash,
                        itemStyle: {
                            color: '#351be6'
                        }
                    },
                    {
                        name: '充值',
                        type: 'line',
                        stack: 'Total',
                        areaStyle: {
                            opacity: 0.3
                        },
                        emphasis: {
                            focus: 'series'
                        },
                        data: res.data.series.recharge,
                        itemStyle: {
                            color: '#f12de0'
                        }
                    }
                ]
            };
            statistics.setOption(option);

            // 响应式处理
            window.addEventListener('resize', function () {
                statistics.resize();
            });
            }
        });
    }

    loadDashboardData(0);
    loadWeekStatistics();

    $('.dashboard-data-type').change(function (e) {
        loadDashboardData(this.value);
    });
}();