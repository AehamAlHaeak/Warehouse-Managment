<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class expiration_not extends Notification
{
    use Queueable;
     public $imp_prod;
       public function __construct($imp_prod)
    {
        $this->imp_prod=$imp_prod;
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
             "msg"=>"the product from import operation final report is",
             "import operation product"=>$this->imp_prod
            
        ];
    }
}
