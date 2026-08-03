<?php

namespace App\Console\Commands;

use App\Models\DoctorAttendance;
use Illuminate\Console\Command;

class AutoCheckoutDoctors extends Command
{
    /**
     * يعمل يوميًا الساعة 4 عصرًا (نهاية دوام العيادة) — راجع bootstrap/app.php
     */
    protected $signature = 'attendance:auto-checkout';

    protected $description = 'تسجيل انصراف تلقائي لكل دكتور سجّل حضوره اليوم ولم يسجّل انصرافه';

    public function handle(): int
    {
        $now = now();

        $count = DoctorAttendance::whereDate('date', today())
            ->whereNull('check_out_at')
            ->update([
                'check_out_at' => $now,
                'is_auto_checkout' => true,
            ]);

        $this->info("تم تسجيل انصراف تلقائي لـ {$count} دكتور.");

        return self::SUCCESS;
    }
}
