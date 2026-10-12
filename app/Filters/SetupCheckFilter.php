<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Refuses to serve the app until a real JWT_SECRET is configured.
 *
 * JWT_SECRET both signs API logins AND derives the key that encrypts every
 * tenant's stored WhatsApp/OpenAI tokens. Left at the shipped placeholder, all
 * installs would share a known key — so we hard-stop with setup instructions
 * instead of running insecurely.
 */
class SetupCheckFilter implements FilterInterface
{
    /** Values that mean "not configured yet". */
    private const PLACEHOLDERS = [
        '',
        'CHANGE_ME_to_a_long_random_unique_string',
        'default_secret_change_me',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $secret = (string) env('JWT_SECRET', '');

        if (! in_array($secret, self::PLACEHOLDERS, true) && strlen($secret) >= 16) {
            return; // configured — carry on
        }

        $html = <<<HTML
<!doctype html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup required</title>
<style>
  body{font-family:system-ui,Segoe UI,Roboto,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px}
  .card{background:#1e293b;border:1px solid #334155;border-radius:14px;max-width:560px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,.4)}
  h1{font-size:1.35rem;margin:0 0 12px}
  code{background:#0f172a;border:1px solid #334155;border-radius:6px;padding:2px 8px;color:#7dd3fc;font-size:.9em}
  pre{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:14px;overflow:auto;color:#a7f3d0}
  .tag{display:inline-block;background:#f59e0b;color:#111;font-weight:700;font-size:.72rem;padding:3px 10px;border-radius:999px;margin-bottom:14px}
</style></head>
<body><div class="card">
  <div class="tag">SETUP REQUIRED</div>
  <h1>Set your JWT_SECRET to continue</h1>
  <p>For security, WALE won't run until you set a unique <code>JWT_SECRET</code>
     in your <code>.env</code> file. It encrypts the WhatsApp &amp; OpenAI tokens
     your customers save, so it must be unique to your install.</p>
  <p>Generate a strong value:</p>
  <pre>php -r "echo bin2hex(random_bytes(32));"</pre>
  <p>Paste it into <code>.env</code>:</p>
  <pre>JWT_SECRET = your-generated-value</pre>
  <p>Then reload this page. See <code>INSTALL.md</code> for full setup steps.</p>
</div></body></html>
HTML;

        return service('response')
            ->setStatusCode(503)
            ->setBody($html);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
