<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function index($code)
    {
        // Deep link custom scheme
        $deepLink = "studify://join?code={$code}";

        // Fallback URL (Local download page since not on Play Store)
        $fallbackUrl = route('app.download');

        return view('invite', compact('deepLink', 'fallbackUrl', 'code'));
    }
}
