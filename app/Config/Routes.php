<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ========================================================================
// PUBLIC / AUTH ROUTES  (no filter)
// ========================================================================
$routes->get('/',          'Home::index');
$routes->get('/login',     'Auth\AuthController::loginForm');
$routes->post('/login',    'Auth\AuthController::login');
// Public self-registration is DISABLED — tenants are created by the Super Admin
// (Admin → All Tenants → Add Tenant). Re-enable the two routes below when the
// payment-gateway signup flow is added. /register redirects to login for now.
$routes->get('/register',  static fn() => redirect()->to('/login'));
// $routes->get('/register',  'Auth\AuthController::registerForm');
// $routes->post('/register', 'Auth\AuthController::register');
$routes->get('/logout',    'Auth\AuthController::logout');

// Password reset (forgot password → emailed link → set new password)
$routes->get('/forgot-password',           'Auth\AuthController::forgotPasswordForm');
$routes->post('/forgot-password',          'Auth\AuthController::forgotPassword');
$routes->get('/reset-password/(:segment)', 'Auth\AuthController::resetPasswordForm/$1');
$routes->post('/reset-password/(:segment)','Auth\AuthController::resetPassword/$1');

// Public sales funnel (pricing → checkout → payment → auto-provision account)
$routes->get('pricing',             'SalesController::pricing');
$routes->get('checkout/(:segment)', 'SalesController::checkout/$1');
$routes->post('checkout',           'SalesController::process');
$routes->get('payment/success',     'SalesController::success');
$routes->get('payment/cancel',      'SalesController::cancel');

// ========================================================================
// ADMIN PANEL ROUTES  (session-protected via auth filter)
// ========================================================================
$routes->group('panel', ['filter' => 'auth'], static function ($routes) {

    $routes->get('dashboard', 'Admin\DashboardController::index');
    $routes->get('inactive',  'Admin\AccountController::inactive'); // subscription gate landing

    // Forced password change (seeded default accounts) — auth filter keeps the
    // user here until done.
    $routes->get('security/password',  'Admin\SecurityController::passwordForm');
    $routes->post('security/password', 'Admin\SecurityController::updatePassword');
    $routes->get('plan',      'Admin\AccountController::plan');     // plan & usage page
    $routes->get('notifications',      'Admin\NotificationsController::index'); // full history
    $routes->get('notifications/read', 'Admin\NotificationsController::read');  // mark all read
    $routes->get('notifications/clear','Admin\NotificationsController::clear');  // clear history

    // Leads
    $routes->get('leads',                        'Admin\LeadsController::index');
    $routes->get('leads/export',                 'Admin\LeadsController::export');
    $routes->get('leads/create',                 'Admin\LeadsController::create');
    $routes->post('leads/store',                 'Admin\LeadsController::store');
    $routes->get('leads/(:num)',                 'Admin\LeadsController::show/$1');
    $routes->get('leads/edit/(:num)',            'Admin\LeadsController::edit/$1');
    $routes->post('leads/update/(:num)',         'Admin\LeadsController::update/$1');
    $routes->get('leads/delete/(:num)',          'Admin\LeadsController::delete/$1');
    $routes->post('leads/(:num)/note',           'Admin\LeadsController::addNote/$1');
    $routes->post('leads/(:num)/status',         'Admin\LeadsController::updateStatus/$1');

    // Conversations
    $routes->get('conversations',                        'Admin\ConversationsController::index');
    $routes->get('conversations/(:num)',                 'Admin\ConversationsController::show/$1');
    $routes->post('conversations/(:num)/send',           'Admin\ConversationsController::sendMessage/$1');
    $routes->post('conversations/(:num)/toggle-ai',      'Admin\ConversationsController::toggleAi/$1');
    $routes->get('conversations/(:num)/close',           'Admin\ConversationsController::close/$1');
    $routes->get('conversations/(:num)/poll',            'Admin\ConversationsController::poll/$1');

    // WhatsApp message templates (re-engage / start chat outside 24h window)
    $routes->get('templates',               'Admin\TemplatesController::index');
    $routes->get('templates/sync',          'Admin\TemplatesController::sync');
    $routes->get('templates/send',          'Admin\TemplatesController::compose');
    $routes->post('templates/send',         'Admin\TemplatesController::send');
    $routes->get('templates/bulk',          'Admin\TemplatesController::bulk');
    $routes->post('templates/bulk/send',    'Admin\TemplatesController::bulkSend');

    // Settings
    $routes->get('settings/ai',            'Admin\SettingsController::ai');
    $routes->post('settings/ai',           'Admin\SettingsController::saveAi');
    $routes->post('settings/ai/test',      'Admin\SettingsController::testAi');
    $routes->get('settings/whatsapp',      'Admin\SettingsController::whatsapp');
    $routes->post('settings/whatsapp',     'Admin\SettingsController::saveWhatsapp');
    $routes->get('settings/profile',       'Admin\SettingsController::profile');
    $routes->post('settings/profile',      'Admin\SettingsController::saveProfile');

    // Email / SMTP (superadmin only — guarded inside controller)
    $routes->get('settings/email',         'Admin\EmailSettingsController::index');
    $routes->post('settings/email',        'Admin\EmailSettingsController::save');
    $routes->post('settings/email/test',   'Admin\EmailSettingsController::sendTest');

    // Branding / White-Label (superadmin only — guarded inside controller)
    $routes->get('settings/branding',                    'Admin\BrandingController::index');
    $routes->post('settings/branding',                   'Admin\BrandingController::save');
    $routes->get('settings/branding/remove/(:segment)',  'Admin\BrandingController::removeAsset/$1');

    // Sales page & Payments (superadmin only — guarded inside controller)
    $routes->get('settings/sales',   'Admin\SalesSettingsController::index');
    $routes->post('settings/sales',  'Admin\SalesSettingsController::save');

    // Orders / Payments (superadmin only — guarded inside controller)
    $routes->get('orders',                 'Admin\OrdersController::index');
    $routes->get('orders/(:num)',          'Admin\OrdersController::show/$1');
    $routes->post('orders/(:num)/status',  'Admin\OrdersController::updateStatus/$1');

    // Plans (superadmin only — guarded inside controller)
    $routes->get('plans',                 'Admin\PlansController::index');
    $routes->get('plans/create',          'Admin\PlansController::create');
    $routes->post('plans/store',          'Admin\PlansController::store');
    $routes->get('plans/(:num)/edit',     'Admin\PlansController::edit/$1');
    $routes->post('plans/(:num)/update',  'Admin\PlansController::update/$1');
    $routes->get('plans/(:num)/toggle',   'Admin\PlansController::toggle/$1');
    $routes->get('plans/(:num)/delete',   'Admin\PlansController::delete/$1');

    // Funnels
    $routes->get('funnels',                   'Admin\FunnelsController::index');
    $routes->get('funnels/import',            'Admin\FunnelsController::importForm');
    $routes->post('funnels/import',           'Admin\FunnelsController::importGenerate');
    $routes->get('funnels/sample',            'Admin\FunnelsController::sample');
    $routes->get('funnels/create',            'Admin\FunnelsController::create');
    $routes->post('funnels/store',            'Admin\FunnelsController::store');
    $routes->get('funnels/(:num)/edit',       'Admin\FunnelsController::edit/$1');
    $routes->post('funnels/(:num)/update',    'Admin\FunnelsController::update/$1');
    $routes->get('funnels/(:num)/toggle',     'Admin\FunnelsController::toggle/$1');
    $routes->get('funnels/(:num)/delete',     'Admin\FunnelsController::delete/$1');
    $routes->get('funnels/sessions',          'Admin\FunnelsController::sessions');
    $routes->post('funnels/start',            'Admin\FunnelsController::start');

    // Analytics
    $routes->get('analytics',      'Admin\AnalyticsController::index');
    $routes->get('analytics/data', 'Admin\AnalyticsController::data');

    // Automations
    $routes->get('automations',               'Admin\AutomationsController::index');
    $routes->get('automations/create',        'Admin\AutomationsController::create');
    $routes->post('automations/store',        'Admin\AutomationsController::store');
    $routes->get('automations/(:num)/edit',   'Admin\AutomationsController::edit/$1');
    $routes->post('automations/(:num)/update','Admin\AutomationsController::update/$1');
    $routes->get('automations/(:num)/toggle', 'Admin\AutomationsController::toggle/$1');
    $routes->get('automations/(:num)/delete', 'Admin\AutomationsController::delete/$1');
    $routes->get('automations/logs',          'Admin\AutomationsController::logs');

    // Tenant management (superadmin only — guarded inside controller)
    $routes->get('tenants',                      'Admin\TenantsController::index');
    $routes->get('tenants/create',               'Admin\TenantsController::create');
    $routes->post('tenants/store',               'Admin\TenantsController::store');
    $routes->get('tenants/(:num)',               'Admin\TenantsController::show/$1');
    $routes->get('tenants/(:num)/edit',          'Admin\TenantsController::edit/$1');
    $routes->post('tenants/(:num)/update',       'Admin\TenantsController::update/$1');
    $routes->get('tenants/(:num)/toggle',        'Admin\TenantsController::toggleStatus/$1');
    $routes->get('tenants/(:num)/delete',        'Admin\TenantsController::delete/$1');
    $routes->post('tenants/(:num)/subscription', 'Admin\TenantsController::saveSubscription/$1');
});

// ========================================================================
// REST API v1 — Public endpoints (no JWT)
// ========================================================================
$routes->post('api/v1/auth/login',    'Api\AuthApiController::login');
$routes->post('api/v1/auth/register', 'Api\AuthApiController::register');
$routes->post('api/v1/auth/refresh',  'Api\AuthApiController::refresh');

// ========================================================================
// REST API v1 — Protected endpoints (JWT via apiAuth filter)
// ========================================================================
$routes->group('api/v1', ['filter' => 'apiAuth'], static function ($routes) {

    // Auth (token-required)
    $routes->get('auth/me',     'Api\AuthApiController::me');
    $routes->post('auth/logout','Api\AuthApiController::logout');

    // Leads CRUD
    $routes->get('leads',                       'Api\LeadsApiController::index');
    $routes->post('leads',                      'Api\LeadsApiController::create');
    $routes->get('leads/(:num)',                'Api\LeadsApiController::show/$1');
    $routes->put('leads/(:num)',                'Api\LeadsApiController::update/$1');
    $routes->delete('leads/(:num)',             'Api\LeadsApiController::delete/$1');
    // Lead notes & status
    $routes->get('leads/(:num)/notes',          'Api\LeadsApiController::notes/$1');
    $routes->post('leads/(:num)/notes',         'Api\LeadsApiController::addNote/$1');
    $routes->post('leads/(:num)/status',        'Api\LeadsApiController::updateStatus/$1');
    $routes->get('leads/(:num)/timeline',       'Api\LeadsApiController::timeline/$1');

    // Conversations
    $routes->get('conversations',                    'Api\ConversationsApiController::index');
    $routes->get('conversations/(:num)',             'Api\ConversationsApiController::show/$1');
    $routes->post('conversations/(:num)/close',      'Api\ConversationsApiController::close/$1');

    // Messages
    $routes->get('conversations/(:num)/messages',   'Api\MessagesApiController::index/$1');
    $routes->post('conversations/(:num)/messages',  'Api\MessagesApiController::send/$1');

    // Dashboard stats
    $routes->get('stats', 'Api\StatsApiController::index');

    // AI config & test
    $routes->get('ai/config',    'Api\AiApiController::getConfig');
    $routes->put('ai/config',    'Api\AiApiController::updateConfig');
    $routes->post('ai/test',     'Api\AiApiController::test');
    $routes->get('ai/variables', 'Api\AiApiController::variables');

    // WhatsApp messaging
    $routes->post('whatsapp/send',           'Api\WhatsAppApiController::send');
    $routes->post('whatsapp/send-template',  'Api\WhatsAppApiController::sendTemplate');
    $routes->get('whatsapp/messages',        'Api\WhatsAppApiController::messages');
    $routes->get('whatsapp/conversations',   'Api\WhatsAppApiController::conversations');
});

// ========================================================================
// WHATSAPP WEBHOOK  (no auth — token verified inside controller)
// ========================================================================
$routes->get('webhook/whatsapp',  'Webhook\WhatsAppWebhookController::verify');
$routes->post('webhook/whatsapp', 'Webhook\WhatsAppWebhookController::handle');
