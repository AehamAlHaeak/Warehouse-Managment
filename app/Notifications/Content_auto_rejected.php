<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Content_auto_rejected extends Notification
{
    use Queueable;

    protected $place;
    protected $why;
    public function __construct($place,$why)
    {
       $this->place=$place;
       $this->why=$why;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
          return ['database', 'broadcast'];
    }

   
    public function toArray(object $notifiable): array
    {
        return [
            "msg"=>"the continers in this place are auto rejected because $this->why",
            "place_type"=>strtolower(class_basename($this->place)),
            "place_id"=>$this->place->id
        ];
    }
}
