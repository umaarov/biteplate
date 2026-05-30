<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Auth\CurrentStaff;
use App\Domain\Staff\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: `->middleware('permission:view_billing')`. Delegates the decision
 * straight to the staff member's own permission set, so adding a role or moving a
 * capability never touches this middleware (Scenario E).
 */
final class RequirePermission
{
    public function __construct(private readonly CurrentStaff $current)
    {
    }

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $needed = Permission::tryFrom($permission);

        if ($needed === null || ! $this->current->can($needed)) {
            abort(403, 'Your role does not have permission to '.str_replace('_', ' ', $permission).'.');
        }

        return $next($request);
    }
}
