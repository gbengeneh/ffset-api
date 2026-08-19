<?php
namespace App\Jobs;
use App\Models\MarketplaceOrder;use App\Services\MarketplaceNotificationService;use Illuminate\Contracts\Queue\ShouldQueue;use Illuminate\Foundation\Queue\Queueable;
class SendMarketplaceOrderNotification implements ShouldQueue {use Queueable;public int $tries=3;public array $backoff=[30,120,300];public function __construct(public int $orderId,public string $event){}public function handle(MarketplaceNotificationService $notifications):void{$order=MarketplaceOrder::find($this->orderId);if($order)$notifications->send($order,$this->event);}}
