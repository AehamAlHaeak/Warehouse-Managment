<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Transfer_left extends Notification
{
    use Queueable;

    
    public function __construct()
    {
        //
    }

   
    public function via(object $notifiable): array
    {
          return ['database', 'broadcast'];
    }

    
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
