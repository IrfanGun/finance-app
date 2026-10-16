<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KeepCmsNavigation
{
    public function handle(Request $request, Closure $next): Response
    {
        $refererPath = parse_url(
            (string) $request->headers->get('referer'),
            PHP_URL_PATH,
        );

        if (
            $request->isMethod('GET')
            && is_string($refererPath)
            && str_starts_with($refererPath, '/cms/')
            && ! $request->is('cms', 'cms/*')
            && ! $request->is('forgot-password', 'register', 'reset-password', 'reset-password/*')
        ) {
            return redirect()->to('/cms'.$request->getRequestUri());
        }

        return $next($request);
    }
}
