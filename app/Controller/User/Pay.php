<?php
declare(strict_types=1);

namespace App\Controller\User;


use App\Controller\Base\View\User;
use App\Interceptor\Waf;
use App\Model\Order;
use App\Model\OrderOption;
use Kernel\Annotation\Interceptor;
use Kernel\Exception\JSONException;
use Kernel\Exception\ViewException;
use Kernel\Util\View;

#[Interceptor(Waf::class)]
class Pay extends User
{
    /**
     * @return string
     * @throws JSONException
     * @throws ViewException
     * @throws \SmartyException
     */
    public function order(): string
    {
        if (!isset($_GET['_PARAMETER'][0]) || !isset($_GET['_PARAMETER'][1])) {
            return '订单不存在';
        }

        $tradeNo = $_GET['_PARAMETER'][0];
        $type = (int)$_GET['_PARAMETER'][1];
        //获取订单信息
        $order = Order::with(['pay'])->where("trade_no", $tradeNo)->first();
        if (!$order) {
            return '订单不存在';
        }

        if (!$order->pay) {
            return '支付方式不存在';
        }

        $data = OrderOption::get($order->id);

        if ($type == 2) {
            if (!$data) {
                throw new JSONException("参数错误");
            }
            return $this->render("正在下单，请稍后..", "Submit.html", [
                "url" => $order->pay_url,
                "data" => $data
            ]);
        }

        $html = "{$order->pay->handle}/View/{$order->pay->code}.html";

        // 插件自带视图文件则优先使用；否则直接渲染插件在 trade() 中返回的 HTML(pay_url)
        if (is_file(BASE_PATH . '/app/Pay/' . $html)) {
            return View::render($html, ['order' => $order, 'option' => $data], BASE_PATH . '/app/Pay/');
        }

        if ($order->pay_url === null || $order->pay_url === '') {
            throw new JSONException("视图不存在");
        }

        return '<!DOCTYPE html><html lang="zh"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . htmlspecialchars($order->pay->name ?? '订单支付') . '</title></head>'
            . '<body style="margin:0;background:#f5f6fa;display:flex;align-items:center;justify-content:center;min-height:100vh;">'
            . '<div style="max-width:420px;width:100%;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;">'
            . $order->pay_url
            . '</div></body></html>';
    }
}