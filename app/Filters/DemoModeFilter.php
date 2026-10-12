<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Read-only guard for the public review demo (DEMO_MODE=true in .env).
 *
 * When demo mode is on, every state-changing request under the panel is blocked
 * so buyers can freely explore all screens without altering the shared demo data.
 * A no-op when DEMO_MODE is off, so it is safe to keep registered on panel/*.
 *
 * Blocked: any non-GET request (form submits, AJAX POSTs) + the destructive GET
 * action links (delete / toggle / close / clear / sync / remove / mark-read).
 * Everything else (viewing pages, opening create/edit forms, CSV export) is allowed.
 */
class DemoModeFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('setting');

        if (! demo_mode()) {
            return; // feature off — do nothing
        }

        $isWrite    = strtoupper($request->getMethod()) !== 'GET';
        $path       = $request->getUri()->getPath();
        $blockedGet = (bool) preg_match('#/(delete|toggle|close|clear|sync|remove|read)(/|$)#i', $path);

        if (! $isWrite && ! $blockedGet) {
            return; // safe read — allow
        }

        // AJAX callers expect JSON; everyone else gets a flash + redirect back.
        if ($request->isAJAX()) {
            return service('response')->setStatusCode(403)
                ->setJSON(['success' => false, 'error' => lang('Nav.demoBlocked')]);
        }

        return redirect()->back()->with('error', lang('Nav.demoBlocked'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
