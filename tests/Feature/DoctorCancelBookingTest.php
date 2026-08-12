<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DoctorDayAssignment;
use App\Models\User;
use App\Notifications\BookingCancelledByClinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * إلغاء الدكتور لحجز مريض: يجب أن يُسجَّل في سجل النشاط ويُشعَر المريض —
 * قبل ذلك كان الإلغاء صامتًا تمامًا (لا سجل ولا إشعار) فيمكن أن يحضر
 * المريض لموعد ملغى دون أن يعلم.
 *
 * كل الحجوزات هنا تقع يوم "اليوم" المُجمَّد (أحد ثابت — راجع today())،
 * ويُعيَّن هذا اليوم صراحة للدكتور الفاعل قبل كل محاولة إلغاء، وإلا رفض
 * DoctorController::cancelBooking الطلب بـ403 (الحجز يقع بيوم غير مُعيَّن له).
 */
class DoctorCancelBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** أحد ثابت — يبقى نفس اليوم بصرف النظر عن يوم تشغيل الاختبارات الفعلي */
    private function today(): Carbon
    {
        return Carbon::create(2026, 8, 16);
    }

    private function doctor(): User
    {
        return User::factory()->create(['role' => 'doctor', 'identifier' => fake()->unique()->numerify('###')]);
    }

    private function assignToday(User $doctor): void
    {
        DoctorDayAssignment::create(['day_of_week' => $this->today()->dayOfWeek, 'doctor_id' => $doctor->id]);
    }

    private function patient(string $role = 'student'): User
    {
        return User::factory()->create([
            'role' => $role,
            'identifier' => fake()->unique()->numerify('########'),
        ]);
    }

    private function bookingFor(User $patient): Booking
    {
        return Booking::create([
            'user_id' => $patient->id,
            'booking_date' => $this->today(),
            'booking_hour' => 9,
            'booking_minute' => 30,
            'price' => Booking::PRICE,
            'status' => 'confirmed',
        ]);
    }

    public function test_cancelling_marks_the_booking_cancelled(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));
        $doctor = $this->doctor();
        $this->assignToday($doctor);
        $booking = $this->bookingFor($this->patient());

        $response = $this->actingAs($doctor)
            ->post(route('doctor.bookings.cancel', $booking));

        $response->assertSessionHas('success');
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_the_patient_receives_a_cancellation_notification(): void
    {
        Notification::fake();
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        $this->assignToday($doctor);
        $patient = $this->patient();
        $booking = $this->bookingFor($patient);

        $this->actingAs($doctor)->post(route('doctor.bookings.cancel', $booking));

        Notification::assertSentTo($patient, BookingCancelledByClinic::class);
        Notification::assertSentToTimes($patient, BookingCancelledByClinic::class, 1);
    }

    public function test_the_notification_body_names_the_date_and_time_and_the_clinic(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        $this->assignToday($doctor);
        $patient = $this->patient();
        $booking = $this->bookingFor($patient);

        $this->actingAs($doctor)->post(route('doctor.bookings.cancel', $booking));

        $data = $patient->fresh()->notifications()->first()->data;

        $this->assertSame('booking_cancelled', $data['type']);
        $this->assertSame('notifications.booking_cancelled.title', $data['title_key']);
        $this->assertSame('notifications.booking_cancelled.body', $data['body_key']);
        $this->assertSame($booking->booking_date->translatedFormat('d F Y'), $data['body_params']['date']);
        $this->assertSame('9:30 '.__('common.time.am'), $data['body_params']['time']);
        $this->assertStringContainsString('من قبل العيادة', __('notifications.booking_cancelled.body', $data['body_params']));
    }

    public function test_the_notification_is_visible_in_the_patients_notification_panel(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        $this->assignToday($doctor);
        $patient = $this->patient();
        $booking = $this->bookingFor($patient);

        $this->actingAs($doctor)->post(route('doctor.bookings.cancel', $booking));

        // جرس الإشعارات في الهيدر يقرأ من نفس الجدول — الطالب يجب أن يرى النص
        $response = $this->actingAs($patient->fresh())->get(route('dashboard.student'));

        $response->assertOk();
        $response->assertSee('تم إلغاء موعدك');
        $response->assertSee('من قبل العيادة');
    }

    public function test_an_activity_log_entry_is_written_under_the_doctor(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        $this->assignToday($doctor);
        $patient = $this->patient();
        $booking = $this->bookingFor($patient);

        $this->actingAs($doctor)->post(route('doctor.bookings.cancel', $booking));

        // الفاعل هو الدكتور (عمود user_id في activity_logs = الفاعل)
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $doctor->id,
            'action' => 'booking_cancelled_by_doctor',
        ]);

        $log = \App\Models\ActivityLog::where('action', 'booking_cancelled_by_doctor')->first();
        $rendered = $log->renderedDescription();
        $this->assertStringContainsString($patient->name, $rendered);
        $this->assertStringContainsString($booking->booking_date->toDateString(), $rendered);
        $this->assertStringContainsString('9:30 صباحًا', $rendered);
    }

    public function test_an_already_cancelled_booking_cannot_be_cancelled_again(): void
    {
        Notification::fake();
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        $this->assignToday($doctor);
        $patient = $this->patient();
        $booking = $this->bookingFor($patient);
        $booking->update(['status' => 'cancelled']);

        $response = $this->actingAs($doctor)
            ->post(route('doctor.bookings.cancel', $booking));

        $response->assertSessionHas('error');
        Notification::assertNothingSent();
        $this->assertDatabaseMissing('activity_logs', ['action' => 'booking_cancelled_by_doctor']);
    }

    public function test_cancelling_twice_notifies_the_patient_only_once(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        $this->assignToday($doctor);
        $patient = $this->patient();
        $booking = $this->bookingFor($patient);

        $this->actingAs($doctor)->post(route('doctor.bookings.cancel', $booking));
        $this->actingAs($doctor)->post(route('doctor.bookings.cancel', $booking));

        $this->assertSame(1, $patient->fresh()->notifications()->count());
        $this->assertSame(1, \App\Models\ActivityLog::where('action', 'booking_cancelled_by_doctor')->count());
    }

    public function test_a_staff_patient_is_notified_the_same_way(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        $this->assignToday($doctor);
        $patient = $this->patient('staff');
        $booking = $this->bookingFor($patient);

        $this->actingAs($doctor)->post(route('doctor.bookings.cancel', $booking));

        $this->assertSame(1, $patient->fresh()->notifications()->count());
    }

    public function test_cancelling_frees_the_slot_for_someone_else(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));

        $doctor = $this->doctor();
        $this->assignToday($doctor);
        $booking = $this->bookingFor($this->patient());
        $this->actingAs($doctor)->post(route('doctor.bookings.cancel', $booking));

        // الخانة صارت متاحة — active_slot_key يُفرَّغ عند الإلغاء
        $other = $this->patient();
        $response = $this->actingAs($other)
            ->post(route('booking.store'), ['hour' => 9, 'minute' => 30]);

        $response->assertSessionHas('success');
    }

    public function test_a_non_doctor_cannot_cancel_a_booking(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));

        $patient = $this->patient();
        $booking = $this->bookingFor($patient);

        $this->actingAs($patient)->post(route('doctor.bookings.cancel', $booking))->assertForbidden();
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_a_doctor_cannot_cancel_a_booking_on_another_doctors_assigned_day(): void
    {
        Carbon::setTestNow($this->today()->copy()->setTime(8, 0));

        $owningDoctor = $this->doctor();
        $this->assignToday($owningDoctor);

        $otherDoctor = $this->doctor();
        // otherDoctor غير مُعيَّن ليوم "اليوم" إطلاقًا

        $booking = $this->bookingFor($this->patient());

        $response = $this->actingAs($otherDoctor)->post(route('doctor.bookings.cancel', $booking));

        $response->assertForbidden();
        $this->assertSame('confirmed', $booking->fresh()->status);
    }
}
