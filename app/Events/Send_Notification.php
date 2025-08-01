<?php

namespace App\Events;


use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Notifications\Content_auto_rejected;
use App\Notifications\Load_incoming;
use App\Notifications\Take_new_task;
use App\Notifications\Transfer_left;
use App\Notifications\Cut_actual_task;
use App\Notifications\Importing_failed;
use App\Notifications\Importing_seccess;
use App\Notifications\Send_the_order_faild;
use App\Notifications\Shortage_of_inventory;
use App\Notifications\Violation_in_element;
use App\Models\Employe;
use App\Models\User;

class Send_Notification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $destination;

    protected $notification_object;
    public function __construct($destination, $notification_object)
    {
        $this->destination = $destination;
        $this->notification_object = $notification_object;
    }

    public function broadcastOn(): Channel
    {
        $type = strtolower(class_basename($this->destination));
        $id = $this->destination->id;

        return new Channel("test.public.channel");
    }
//     public function broadcastOn(): Channel   // public channel to try the service
// {
//     return new Channel('test.public.channel');
// }
    public function broadcastAs(): string
    {
        return 'notification-event';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => class_basename($this->notification_object),
            'data' => $this->notification_object->toArray($this->destination),
        ];
    }
}
