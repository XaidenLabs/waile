<?php

namespace App\Controllers;

class Home extends BaseController
{
    /**
     * Root URL — send logged-in users to the dashboard, everyone else to login.
     */
    public function index()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/panel/dashboard');
        }

        return redirect()->to('/login');
    }
}
