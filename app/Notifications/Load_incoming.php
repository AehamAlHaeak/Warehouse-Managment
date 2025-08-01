<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Load_incoming extends Notification
{
    use Queueable;
    protected $load_id;
  
    public function __construct($load_id)
    {
        $this->load_id = $load_id;
       
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
           "msg"=>"new load incoming to quality analise",
           "load_id"=>$this->load_id
            
        ];
    }
}
