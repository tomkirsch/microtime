<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $app = new \Config\App();
        return view('welcome_message', ['currentDate' => $app->currentDate()]);
    }
}
