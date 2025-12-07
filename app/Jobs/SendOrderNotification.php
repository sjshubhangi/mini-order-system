<?php
/**
 * Senior note:
 * - Keep payload minimal; load relations lazily to avoid heavy serialization.
 * - Mail logs to file in local (MAIL_MAILER=log).
 */

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderNotificationMail;

class SendOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        $order = $this->order->load('product.vendor');
        $vendorEmail = optional($order->product->vendor)->email ?? 'vendor@example.com';

        // Mail::raw("New Order #{$order->id} for product ID {$order->product_id}", function ($msg) use ($vendorEmail) {
        //     $msg->to($vendorEmail)->subject('New Order Notification');
        // });

        Mail::to($vendorEmail)->send(new OrderNotificationMail($order));
    }
}
