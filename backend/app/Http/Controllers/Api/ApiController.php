<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * Base class for every API controller (2026-09-12-002, D-002).
 *
 * The response envelope is FROZEN — it mirrors the shared TypeScript contract
 * (packages/types, D-005) exactly:
 *
 *   ApiResponse<T>       { success, message, data?, errors? }
 *   PaginatedResponse<T> { success, data: T[], meta: { current_page, last_page, per_page, total } }
 *
 * No Laravel API Resource classes are used in this project — models are
 * serialized directly (->select() / with('rel:id,col')) inside this envelope.
 */
abstract class ApiController extends Controller
{
    protected function success(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function error(string $message = 'Error', int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    protected function notFound(string $message = 'Not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    protected function validationError(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Paginate a query into the frozen envelope — flat data array plus a
     * four-field meta block, NOT Laravel's default paginator JSON.
     * `$transform` maps each row into its contract shape when the model's
     * default serialization isn't enough.
     */
    protected function paginated(Builder $query, int $perPage = 15, ?callable $transform = null): JsonResponse
    {
        $paginated = $query->paginate($perPage);
        $items = $paginated->items();
        if ($transform !== null) {
            $items = array_map($transform, $items);
        }

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }
}
