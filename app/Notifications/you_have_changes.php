<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class you_have_changes extends Notification
{
    use Queueable;

    protected $mesage;
    public function __construct($mesage)
    {
        $this->mesage = $mesage;
    }

  
     public function via(object $notifiable): array
    {
    return ['database', 'broadcast'];
    }
    
   
    
    public function toArray(object $notifiable): array
    {
        return [
            "msg"=>$this->mesage
        ];
    }
}
