<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

abstract class Controller
{
    private static ?bool $paymentsTableExists = null;

    protected function respondWithPagination(LengthAwarePaginator $paginator, $data): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    protected function resolvePerPage(Request $request, int $default = 20): int
    {
        $perPage = (int) $request->input('perPage', $default);

        return max(5, min(100, $perPage));
    }

    protected function hasPaymentsTable(): bool
    {
        if (self::$paymentsTableExists !== null) {
            return self::$paymentsTableExists;
        }

        try {
            self::$paymentsTableExists = Schema::hasTable('payments');
        } catch (\Throwable $exception) {
            self::$paymentsTableExists = false;
        }

        return self::$paymentsTableExists;
    }
}
