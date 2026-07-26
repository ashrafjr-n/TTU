<?php

namespace App\Console\Commands;

use App\Models\BookingRequest;
use Illuminate\Console\Command;

class ExpireBookingRequests extends Command
{
    protected $signature = 'bookings:expire-requests';

    protected $description = 'تحويل طلبات الحجز المنتهية الصلاحية إلى حالة "منتهية"';

    public function handle(): void
    {
        $expiredCount = BookingRequest::where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
                'decided_at' => now(),
            ]);

        if ($expiredCount > 0) {
            $this->info("تم تحويل {$expiredCount} طلب/طلبات إلى منتهية الصلاحية.");
        } else {
            $this->info('لا يوجد طلبات منتهية الصلاحية حاليًا.');
        }
    }
}