<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class send_products_for_me extends Notification
{
    use Queueable;

   public $destination_type;
   public $destination_id;
   public $products;
  
    public function __construct($destination_type,$products,$destination_id)
    {
        $this->destination_type=$destination_type;
        $this->products=$products;
        $this->destination_id=$destination_id;
    
    }

   
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

   
    public function toArray(object $notifiable): array
    {
        return [
           "destination_type"=>$this->destination_type,
           "destination_id"=>$this->destination_id,
           "products"=>$this->products
        ];
    }
}
