<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Sell_under_work extends Notification
{
    use Queueable;

    protected $invoice;
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
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
            "msg"=>"your invoice under work",
            "invoice_id"=>$this->invoice->id,
            "type"=>$this->invoice->type
        ];
    }
}
