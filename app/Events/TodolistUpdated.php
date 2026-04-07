<?php

namespace App\Events;

use App\Models\Todolist;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TodolistUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $todolist;

    /**
     * Create a new event instance.
     */
    public function __construct(Todolist $todolist)
    {
        $this->todolist = $todolist;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('todolists.' . $this->todolist->tenant_id),
        ];
    }

    public function broadcastWith(): array
    {
        $todo = $this->todolist;
        return ['todolist' => [
            'id' => $todo->id,
            'Title' => $todo->Title,
            'Description' => $todo->Description,
            'end_date' => $todo->end_date ? $todo->end_date->toDateString() : null,
            'start_date' => $todo->start_date ? $todo->start_date->toDateString() : null,
            'status_id' => $todo->status_id,
            'status' => $todo->status ? $todo->status->name : null,
            'priority_id' => $todo->priority_id,
            'priority' => $todo->priority ? $todo->priority->name : null,
            'subtasks' => $todo->children->pluck('Title'),
            'pics' => $todo->todolistPICs->map(function ($pic) {
                return [
                    'id' => $pic->id,
                    'todolist_id' => $pic->todolist_id,
                    'user_id' => $pic->user_id,
                    'tenant_id' => $pic->tenant_id,
                    'user' => $pic->user ? ['name' => $pic->user->name] : null,
                ];
            }),
        ]];
    }
}
