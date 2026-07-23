<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
        return response()->json(Event::orderBy('id')->get());
    }

    public function store(StoreEventRequest $request)
    {
        return response()->json(Event::create($request->validated()), 201);
    }

    public function update(StoreEventRequest $request, Event $event)
    {
        $event->update($request->validated());

        return response()->json($event);
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json(null, 204);
    }
}
