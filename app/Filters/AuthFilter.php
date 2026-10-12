<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Session-based authentication filter for admin panel routes.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Please log in to continue.');
        }

        // A default/seeded password must be replaced before anything else.
        if (session()->get('force_pw') && ! str_starts_with(uri_string(), 'panel/security/password')) {
            return redirect()->to('/panel/security/password');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}
