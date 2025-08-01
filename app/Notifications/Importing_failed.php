<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Importing_failed extends Notification
{
    use Queueable;

    protected $type;
    protected $error;
    public function __construct($type,$error)
    {
        $this->type=$type;
        $this->error=$error;
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
             "msg"=>"$this->type faild because $this->error",
            
        ];
    }
}
