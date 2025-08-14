<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Violation_ocured extends Notification
{
    use Queueable;

   public $place,$violation;
   public function __construct($place,$violation)
    {
        $this->place = $place;
        $this->violation = $violation;
    }

    
    public function via(object $notifiable): array
    {
    return ['database', 'broadcast'];
    }

    
    public function toArray(object $notifiable): array
    {
        return [
          "msg"=>"the place has a violation have ocured the content auto rejected go to deal with the action",
          "vilation_id"=>$this->violation->id,
          "violation_type"=>$this->violation->parameter, 
          "place_type"=>strtolower(class_basename($this->place)),
          "place_id"=>$this->place->id
        ];
    }
}
