<?php

namespace App\Cells;

use App\Models\NotificationModel;

/**
 * Topbar notification bell — rendered on every admin page via view_cell().
 */
class NotificationBell
{
    public function render(): string
    {
        $uid = (int) session()->get('user_id');
        if ($uid <= 0) {
            return '';
        }

        try {
            $model = new NotificationModel();

            return view('admin/partials/notification_bell', [
                'count' => $model->unreadCount($uid),
                'items' => $model->recent($uid, 8),
            ]);
        } catch (\Throwable $e) {
            // e.g. table not migrated yet — fail silently rather than break every page.
            return '';
        }
    }
}
