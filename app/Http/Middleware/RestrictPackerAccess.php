<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RestrictPackerAccess
{
    /**
     * Packer accounts are denied by default — only routes listed here are reachable.
     * Everything else (Master Data besides Finished Products, Distribution, Finance,
     * Reports, and create/edit/delete on Finished Products) stays admin-only.
     */
    private const ALLOWED_ROUTE_PATTERNS = [
        'dashboard',
        'daily-production.*',
        'packer-packs.*',
        'packers.sync',
        'finished-products.index',
        'finished-products.show',
        'finished-products.restock',
        'finished-products.adjust',
        'finished-products.calculate-max',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isPacker()) {
            $routeName = $request->route()?->getName();
            $allowed = collect(self::ALLOWED_ROUTE_PATTERNS)
                ->contains(fn ($pattern) => Str::is($pattern, $routeName));

            abort_unless($allowed, 403, 'This account does not have access to that page.');
        }

        return $next($request);
    }
}
