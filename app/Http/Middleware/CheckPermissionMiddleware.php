<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\{Permission, RoleHasPermission, RolePermission};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CheckPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {

        $user = Auth::user();
        $roleId = $user->role_id;
        $routeName = $request->route()->getName();
        $permission = Permission::where('api_route', $routeName)->first();
        if ($permission) {            

            $hasPermission = RoleHasPermission::where('role_id', $roleId)
            ->where('permission_id', $permission->id)->exists();

            if ($hasPermission) {
                return $next($request);
            } else {
                abort(403, 'Unauthorized');
            }
        }else{
            return $next($request);
        }
    }
}
