<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Services\TelegramNotifier;

class ContactMessageController extends Controller
{
    public function __construct(private TelegramNotifier $telegram) {}

    public function store(StoreContactMessageRequest $request)
    {
        $message = ContactMessage::create($request->validated());

        $this->telegram->send('Contact Message', [
            'Full Name' => $message->name,
            'Email' => $message->email,
            'Message' => $message->message,
        ]);

        return response()->json($message, 201);
    }
}
