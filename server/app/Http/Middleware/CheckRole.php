<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user && $user->employee && $user->employee->Position && in_array($user->employee->Position->position_title, $roles)) {
            return $next($request);
        }

        return response()->json(['message' => "Unauthorized. Only Authorized Positions allowed."], 403);
    }
}
