<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Auth\CurrentStaff;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/** Gate the POS behind a signed-in staff member, and share them with every view. */
final class RequireStaff
{
    public function __construct(private readonly CurrentStaff $current)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->current->check()) {
            return redirect()->route('login');
        }

        View::share('currentStaff', $this->current->staff());

        return $next($request);
    }
}
