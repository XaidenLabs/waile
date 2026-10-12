<?php

namespace App\Filters;

use App\Models\SubscriptionModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Blocks tenant users whose subscription has expired/cancelled, sending them to
 * the "subscription inactive" page. Super admins and users without any
 * subscription (admin-granted accounts) are never gated.
 */
class SubscriptionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Not logged in → let the auth filter handle it.
        if (! session()->get('logged_in')) {
            return;
        }

        // Super admins are never gated.
        if (session()->get('user_role') === 'superadmin') {
            return;
        }

        // Always allow the inactive page and the forced password-change page
        // (avoid redirect loops / lockouts).
        if (in_array(uri_string(), ['panel/inactive', 'panel/security/password'], true)) {
            return;
        }

        $userId = (int) session()->get('user_id');

        if (! (new SubscriptionModel())->isActiveFor($userId)) {
            return redirect()->to('/panel/inactive');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}
