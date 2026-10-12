<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\NotificationModel;

class NotificationsController extends BaseController
{
    /**
     * Full notification history for the current tenant.
     */
    public function index()
    {
        $userId = (int) session()->get('user_id');
        $model  = new NotificationModel();

        $filter  = $this->request->getGet('type'); // error|warning|info|success|null
        $builder = $model->where('user_id', $userId);
        if (in_array($filter, ['error', 'warning', 'info', 'success'], true)) {
            $builder->where('type', $filter);
        }

        $data = [
            'title' => lang('Titles.notifications'),
            'notifications' => $builder->orderBy('id', 'DESC')->paginate(20),
            'pager'         => $model->pager,
            'filter'        => $filter,
            'unread'        => $model->unreadCount($userId),
        ];

        // Opening the list marks everything as read.
        $model->markAllRead($userId);

        return view('admin/notifications/index', $data);
    }

    /**
     * Mark all of the current tenant's notifications as read.
     */
    public function read()
    {
        (new NotificationModel())->markAllRead((int) session()->get('user_id'));

        return redirect()->back();
    }

    /**
     * Clear the tenant's notification history.
     */
    public function clear()
    {
        (new NotificationModel())->clearAll((int) session()->get('user_id'));

        return redirect()->to('panel/notifications')->with('success', lang('Messages.notificationsCleared'));
    }
}
