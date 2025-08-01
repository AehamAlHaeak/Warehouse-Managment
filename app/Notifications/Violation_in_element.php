<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Violation_in_element extends Notification
{
    use Queueable;

    protected $place;
    protected $type;
    public function __construct($place,$type)
    {
        $this->place = $place;
        $this->type = $type;
    }

    
    public function via(object $notifiable): array
    {
    return ['database', 'broadcast'];
    }

    
    public function toArray(object $notifiable): array
    {
        return [
          "msg"=>"the place has a violation after tow hours if not fixed it will be auto reject its content",
          "type"=>$this->type, 
          "place_type"=>strtolower(class_basename($this->place)),
          "place_id"=>$this->place->id
        ];
    }
}
