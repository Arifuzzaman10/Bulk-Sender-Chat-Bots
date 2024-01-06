<?php

namespace App\Http\Middleware;

use App\Company;
use Closure;
use Illuminate\Support\Facades\URL;

class checkActiveCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $tmp = explode('/', URL::current());
        $alias = end($tmp);
        $company = Company::where('subdomain', $alias)->first();

        if ($company->active == 1) {
            return $next($request);
        } else {
            return redirect('/');
        }
    }
}
