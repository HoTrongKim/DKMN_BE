<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Ensure the authenticated user owns one of the required roles.
     *
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user) {
            return $this->denyResponse(401, 'Unauthenticated.');
        }

        if (empty($roles)) {
            return $next($request);
        }

        $currentRole = strtolower((string) ($user->vai_tro ?? ''));
        $allowedRoles = array_map(
            static fn ($role) => strtolower((string) $role),
            $roles
        );

        if (!in_array($currentRole, $allowedRoles, true)) {
            return $this->denyResponse(403, 'Forbidden.');
        }

        return $next($request);
    }

    private function denyResponse(int $status, string $message): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $status);
    }
}
