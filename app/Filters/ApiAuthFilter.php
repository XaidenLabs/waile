<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JwtLibrary;
use App\Models\UserModel;

/**
 * JWT-based authentication filter for API routes.
 * Validates Bearer token and injects user_id into request globals.
 */
class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || ! str_starts_with($authHeader, 'Bearer ')) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => false, 'message' => 'Unauthorized: missing token']);
        }

        $token = substr($authHeader, 7);

        try {
            $jwt     = new JwtLibrary();
            $payload = $jwt->decode($token);

            if (! isset($payload->user_id)) {
                throw new \Exception('Invalid token payload');
            }

            // Attach tenant context to the request so controllers can use it
            $request->userId = (int) $payload->user_id;

            // Plan gate: REST API access is a paid feature.
            if (! (new \App\Services\PlanService((int) $payload->user_id))->hasFeature('api_access')) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON(['status' => false, 'message' => 'Your plan does not include API access. Please upgrade.']);
            }

        } catch (\Exception $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => false, 'message' => 'Unauthorized: ' . $e->getMessage()]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}
