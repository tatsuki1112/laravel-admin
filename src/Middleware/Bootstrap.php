<?php

namespace Encore\Admin\Middleware;

use Closure;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;

class Bootstrap
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        Admin::bootstrap();

        return $next($request);
    }
}
