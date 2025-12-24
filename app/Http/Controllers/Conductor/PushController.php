<?php

namespace App\Http\Controllers\Conductor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required',
            'keys.p256dh' => 'required',
            'keys.auth' => 'required',
        ]);

        $userId = auth()->id();

        DB::table('push_subscriptions')->updateOrInsert(
            ['user_id' => $userId, 'endpoint' => $request->endpoint],
            [
                'p256dh' => $request->input('keys.p256dh'),
                'auth'   => $request->input('keys.auth'),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }
}
