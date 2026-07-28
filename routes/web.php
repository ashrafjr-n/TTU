<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DoctorController;



Route::middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('/dashboard/doctor', [DoctorController::class, 'index'])->name('dashboard.doctor');
    Route::post('/doctor/requests/{bookingRequest}/approve', [DoctorController::class, 'approve'])->name('doctor.requests.approve');
    Route::post('/doctor/requests/{bookingRequest}/reject', [DoctorController::class, 'reject'])->name('doctor.requests.reject');
    Route::post('/doctor/bookings/{booking}/cancel', [DoctorController::class, 'cancelBooking'])->name('doctor.bookings.cancel');
});

// الصفحة الرئيسية - اختيار الدور
Route::get('/', function () {
    return view('welcome');
})->name('home');

// توجيه ذكي بعد تسجيل الدخول حسب الدور
Route::middleware('auth')->get('/dashboard', function () {
    $user = auth()->user();

    return match ($user->role) {
        'student' => redirect()->route('dashboard.student'),
        'staff' => redirect()->route('dashboard.staff'),
        'doctor' => redirect()->route('dashboard.doctor'),
    };
})->name('dashboard');

// لوحة الطالب
Route::get('/dashboard/student', function () {
    $bookings = auth()->user()->bookings()
        ->where('status', 'confirmed')
        ->orderByDesc('booking_date')
        ->orderByDesc('booking_hour')
        ->take(5)
        ->get();

    return view('student.dashboard', ['recentBookings' => $bookings]);
})->name('dashboard.student');

// لوحة الموظف
Route::get('/dashboard/staff', function () {
    $bookings = auth()->user()->bookings()
        ->where('status', 'confirmed')
        ->orderByDesc('booking_date')
        ->orderByDesc('booking_hour')
        ->take(5)
        ->get();

    return view('staff.dashboard', ['recentBookings' => $bookings]);
})->name('dashboard.staff');


// صفحات الحجز (طالب وموظف فقط)
Route::middleware(['auth', 'role:student,staff'])->group(function () {
    Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
    Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
    Route::post('/booking/request', [BookingController::class, 'requestBooking'])->name('booking.request');
    Route::delete('/booking/{booking}', [BookingController::class, 'destroy'])->name('booking.destroy');
});

require __DIR__.'/auth.php';