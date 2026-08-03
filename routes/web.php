<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\AdminController;




Route::middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('/dashboard/doctor', [DoctorController::class, 'index'])->name('dashboard.doctor');
    Route::post('/doctor/bookings/{booking}/cancel', [DoctorController::class, 'cancelBooking'])->name('doctor.bookings.cancel');
});

// الصفحة الرئيسية - اختيار الدور
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/about', 'about')->name('about');

// توجيه ذكي بعد تسجيل الدخول حسب الدور
Route::middleware('auth')->get('/dashboard', function () {
    $user = auth()->user();

    return match ($user->role) {
        'student' => redirect()->route('dashboard.student'),
        'staff' => redirect()->route('dashboard.staff'),
        'doctor' => redirect()->route('dashboard.doctor'),
        'admin' => redirect()->route('admin.dashboard'),
    };
})->name('dashboard');

// لوحة الطالب
Route::get('/dashboard/student', function () {
    $bookings = auth()->user()->bookings()
        ->where('status', 'confirmed')
        ->orderByDesc('booking_date')
        ->orderByDesc('booking_hour')
        ->orderByDesc('booking_minute')
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
        ->orderByDesc('booking_minute')
        ->take(5)
        ->get();

    return view('staff.dashboard', ['recentBookings' => $bookings]);
})->name('dashboard.staff');


// صفحات الحجز (طالب وموظف فقط)
Route::middleware(['auth', 'role:student,staff'])->group(function () {
    Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
    Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
    Route::delete('/booking/{booking}', [BookingController::class, 'destroy'])->name('booking.destroy');
});


Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users/{user}/toggle', [AdminController::class, 'toggleUserStatus'])->name('users.toggle');
    Route::get('/doctors/create', [AdminController::class, 'createDoctor'])->name('doctors.create');
    Route::post('/doctors', [AdminController::class, 'storeDoctor'])->name('doctors.store');
    Route::get('/records', [AdminController::class, 'records'])->name('records');
    Route::post('/records', [AdminController::class, 'storeRecord'])->name('records.store');
    Route::delete('/records/{record}', [AdminController::class, 'destroyRecord'])->name('records.destroy');
});

require __DIR__.'/auth.php';