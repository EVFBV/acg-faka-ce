<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Base\View\Manage;
use App\Interceptor\ManageSession;
use Kernel\Annotation\Interceptor;
use Kernel\Exception\ViewException;

#[Interceptor(ManageSession::class)]
class Store extends Manage
{
    /**
     * 共享店铺
     */
    public function index(): string
    {
        return $this->render("店铺共享", "Shared/Store.html");
    }
}
