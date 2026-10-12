<?php

namespace App\Traits;

/**
 * Standardised JSON response helpers for all API controllers.
 *
 * Every API response follows the envelope:
 *   { "status": bool, "message": string, "data": mixed, "meta": mixed }
 */
trait ApiResponseTrait
{
    protected function success(mixed $data = [], string $message = 'Success', int $code = 200, array $meta = []): \CodeIgniter\HTTP\ResponseInterface
    {
        $body = ['status' => true, 'message' => $message, 'data' => $data];

        if (! empty($meta)) {
            $body['meta'] = $meta;
        }

        return $this->response->setStatusCode($code)->setJSON($body);
    }

    protected function fail(string $message = 'Error', int $code = 400, mixed $errors = null): \CodeIgniter\HTTP\ResponseInterface
    {
        $body = ['status' => false, 'message' => $message];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return $this->response->setStatusCode($code)->setJSON($body);
    }

    protected function notFound(string $message = 'Resource not found'): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fail($message, 404);
    }

    protected function unauthorized(string $message = 'Unauthorized'): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fail($message, 401);
    }

    protected function forbidden(string $message = 'Forbidden'): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fail($message, 403);
    }

    protected function validationError(array $errors): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fail('Validation failed', 422, $errors);
    }

    /** Pagination meta helper — pass CI4 pager object or build manually */
    protected function paginationMeta(int $total, int $perPage, int $currentPage): array
    {
        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $currentPage,
            'last_page'    => (int) ceil($total / max($perPage, 1)),
        ];
    }
}
