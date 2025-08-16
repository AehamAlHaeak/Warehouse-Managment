<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Shortage_of_inventory_in_company extends Notification
{
    use Queueable;

   protected $product;
    public function __construct($product)
    {
        
        $this->product=$product;
    }

    
    public function via(object $notifiable): array
    {
           return ['database', 'broadcast'];
    }

    
    public function toArray(object $notifiable): array
    {
        return [
            "msg"=>"the company inventory is low  ",
           
            "product"=>$this->product,
            
        ];
    }
}
