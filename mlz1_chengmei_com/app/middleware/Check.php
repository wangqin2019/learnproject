<?php

namespace app\middleware;

class Check
{
    public function handle($request, \Closure $next)
    {

    	return $next($request);
    }
}
