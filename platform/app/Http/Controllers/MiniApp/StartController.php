<?php

namespace App\Http\Controllers\MiniApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StartController extends Controller
{
    /** Экран запуска: MAX Bridge передаёт initData, скрипт обменивает её на сессию. */
    public function __invoke(Request $request): View
    {
        return view('miniapp.start', [
            'reason' => $request->query('reason'),
            'demoLogin' => (bool) config('miniapp.demo_login'),
        ]);
    }
}
