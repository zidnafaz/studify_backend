<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeviceTokenController extends Controller
{
    /**
     * Store or update a device token for the authenticated user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'platform' => 'required|string|in:android,ios,web',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('messages.validation_errors'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // Update existing token or create new one
        // We use updateOrCreate to ensure a token is unique in the table
        // But we also want to ensure it belongs to the current user
        // If the token exists but belongs to another user (e.g. logout/login on same device),
        // we should update the user_id.

        $deviceToken = DeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => $user->id,
                'platform' => $request->platform,
            ]
        );

        return response()->json([
            'message' => __('messages.device_token_saved'),
            'data' => $deviceToken,
        ], 200);
    }
}
