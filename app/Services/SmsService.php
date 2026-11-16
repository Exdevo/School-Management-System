<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): void
    {
        Log::info('Parent SMS queued for future Africa\'s Talking integration.', [
            'to' => $phone,
            'message' => $message,
        ]);
    }
}
