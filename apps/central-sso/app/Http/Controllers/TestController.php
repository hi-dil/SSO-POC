<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test($tenant_slug, Request $request)
    {
        return response()->json([
            'message' => 'Test route works',
            'tenant_slug' => $tenant_slug,
            'callback_url' => $request->get('callback_url')
        ]);
    }
}
