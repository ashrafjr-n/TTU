<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VisitReportController;

Route::middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('/dashboard/doctor', [DoctorController::class, 'index'])->name('dashboard.doctor');
    Route::post('/doctor/bookings/{booking}/cancel', [DoctorController::class, 'cancelBooking'])->name('doctor.bookings.cancel');
    Route::post('/doctor/bookings/{booking}/report', [VisitReportController::class, 'store'])->name('doctor.bookings.report.store');
    Route::post('/doctor/attendance/check-in', [DoctorController::class, 'checkIn'])->name('doctor.attendance.checkin');
    Route::post('/doctor/attendance/check-out', [DoctorController::class, 'checkOut'])->name('doctor.attendance.checkout');
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

// لوحتا الطالب والموظف
Route::middleware(['auth', 'role:student'])->get('/dashboard/student', [DashboardController::class, 'student'])->name('dashboard.student');
Route::middleware(['auth', 'role:staff'])->get('/dashboard/staff', [DashboardController::class, 'staff'])->name('dashboard.staff');


// صفحات الحجز (طالب وموظف فقط)
Route::middleware(['auth', 'role:student,staff'])->group(function () {
    Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
    Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
    Route::delete('/booking/{booking}', [BookingController::class, 'destroy'])->name('booking.destroy');
    Route::get('/my-medications', [DashboardController::class, 'medications'])->name('medications.mine');
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
    Route::get('/medications', [AdminController::class, 'medications'])->name('medications');
    Route::post('/medications', [AdminController::class, 'storeMedication'])->name('medications.store');
    Route::put('/medications/{medication}', [AdminController::class, 'updateMedication'])->name('medications.update');
    Route::post('/medications/{medication}/restock', [AdminController::class, 'restockMedication'])->name('medications.restock');
    Route::post('/medications/{medication}/toggle', [AdminController::class, 'toggleMedicationStatus'])->name('medications.toggle');
    Route::get('/attendance', [AdminController::class, 'attendance'])->name('attendance');
    Route::put('/doctors/{doctor}/schedule', [AdminController::class, 'updateDoctorSchedule'])->name('doctors.schedule.update');
});

require __DIR__.'/auth.php';