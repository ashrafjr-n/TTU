<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Models\VisitReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function student(): View
    {
        $user = Auth::user();

        return view('student.dashboard', [
            'recentBookings' => $this->recentBookingsFor($user),
            'activeBooking' => Booking::activeViewDataFor($user),
        ]);
    }

    public function staff(): View
    {
        $user = Auth::user();

        return view('staff.dashboard', [
            'recentBookings' => $this->recentBookingsFor($user),
            'activeBooking' => Booking::activeViewDataFor($user),
        ]);
    }

    /**
     * صفحة "أدويتي" — عرض قراءة فقط لتقارير زيارات المستخدم السابقة.
     */
    public function medications(): View
    {
        $user = Auth::user();

        $reports = VisitReport::with(['medications', 'booking'])
            ->whereHas('booking', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->get();

        return view('medications.mine', [
            'reports' => $reports,
        ]);
    }

    /**
     * آخر 5 حجوزات مؤكدة للمستخدم — مشتركة بين لوحتي الطالب والموظف.
     */
    private function recentBookingsFor(User $user)
    {
        return $user->bookings()
            ->where('status', 'confirmed')
            ->orderByDesc('booking_date')
            ->orderByDesc('booking_hour')
            ->orderByDesc('booking_minute')
            ->take(5)
            ->get();
    }
}
