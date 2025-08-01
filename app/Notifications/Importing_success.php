<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Importing_success extends Notification
{
    use Queueable;

    protected $type;
    public function __construct($type)
    {
        $this->type=$type;
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
            "type"=>$this->type,
            "msg"=>"importing completed succesfuly!"
        ];
    }
}
