<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Models\User;
use App\Notifications\OrderShipped as OrderShippedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendOrderShippedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderShipped $event): void
    {
        $customer_id = $event->order->customer_id;
        $customer = User::find($customer_id);

        Notification::sendNow([$customer], new OrderShippedNotification($event->order));
    }
}
