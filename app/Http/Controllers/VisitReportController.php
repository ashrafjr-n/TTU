<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\DoctorDayAssignment;
use App\Models\Medication;
use App\Models\VisitReport;
use App\Models\VisitReportMedication;
use App\Notifications\VisitReportCompleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitReportController extends Controller
{
    /**
     * إنشاء أو تعديل تقرير زيارة لحجز معيّن (نفس المسار لكلا الحالتين).
     */
    public function store(Request $request, Booking $booking)
    {
        if ($booking->status !== 'confirmed') {
            return back()->with('error', __('doctor.report_modal.not_confirmed'));
        }

        // لا يقدر دكتور يرفق/يعدّل تقريرًا لحجز بيوم مُعيَّن لدكتور آخر —
        // حتى لو عرف رقم الحجز مباشرة (الرابط ليس محميًا بغياب doctor_id
        // بجدول bookings أصلًا، فهذا الفحص هو الحارس الوحيد).
        abort_unless(
            DoctorDayAssignment::doctorIdForDate($booking->booking_date) === Auth::id(),
            403,
            __('doctor.errors.not_your_day')
        );

        if (!$booking->hasStarted()) {
            return back()->with('error', __('doctor.report_modal.not_started_yet'));
        }

        $validated = $request->validate([
            'condition' => 'required|string',
            'examination' => 'required|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'notes' => 'nullable|string',
            'medications' => 'nullable|array',
            'medications.*.medication_id' => ['required', 'distinct', 'exists:medications,id'],
            'medications.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $newMeds = collect($validated['medications'] ?? [])
            ->mapWithKeys(fn ($row) => [(int) $row['medication_id'] => (int) $row['quantity']]);

        $wasEdit = false;

        try {
            DB::transaction(function () use ($booking, $validated, $newMeds, &$wasEdit) {
                // قفل صف الحجز نفسه أولًا: يسلسل أي طلبات متزامنة لإرفاق/تعديل
                // تقرير هذا الحجز (سواء كانت أول عملية إنشاء أو تعديلات متتالية)،
                // فلا يقرأ طلبان "لا يوجد تقرير بعد" معًا قبل أي كومِت.
                Booking::where('id', $booking->id)->lockForUpdate()->first();

                $report = VisitReport::where('booking_id', $booking->id)->lockForUpdate()->first();
                $wasEdit = (bool) $report;

                // الكميات القديمة تُقرأ هنا (بعد القفل)، وليس من بيانات محمّلة
                // مسبقًا عند فتح المودال — حتى لا يُحسب الفرق مقابل حالة قديمة.
                $oldMeds = $report
                    ? VisitReportMedication::where('visit_report_id', $report->id)
                        ->lockForUpdate()
                        ->get()
                        ->pluck('quantity', 'medication_id')
                    : collect();

                $medicationIds = $newMeds->keys()->merge($oldMeds->keys())->unique();
                $medications = Medication::whereIn('id', $medicationIds)->lockForUpdate()->get()->keyBy('id');

                foreach ($medicationIds as $medId) {
                    $diff = $newMeds->get($medId, 0) - $oldMeds->get($medId, 0);

                    if ($diff > 0 && $medications[$medId]->stock_quantity < $diff) {
                        throw ValidationException::withMessages([
                            'medications' => __('doctor.report_modal.insufficient_stock', [
                                'name' => $medications[$medId]->name,
                                'stock' => $medications[$medId]->stock_quantity,
                            ]),
                        ]);
                    }
                }

                if (!$report) {
                    $report = new VisitReport(['booking_id' => $booking->id]);
                }

                $report->fill([
                    'doctor_id' => Auth::id(),
                    'condition' => $validated['condition'],
                    'examination' => $validated['examination'],
                    'diagnosis' => $validated['diagnosis'] ?? null,
                    'treatment_plan' => $validated['treatment_plan'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
                $report->save();

                foreach ($medicationIds as $medId) {
                    $diff = $newMeds->get($medId, 0) - $oldMeds->get($medId, 0);

                    if ($diff !== 0) {
                        // diff سالب (نقص الكمية أو حذف الدواء بالكامل) يعيد الفرق للمخزون
                        $medications[$medId]->decrement('stock_quantity', $diff);
                    }
                }

                $report->medications()->sync(
                    $newMeds->map(fn ($qty) => ['quantity' => $qty])->all()
                );
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        ActivityLog::record(
            Auth::id(),
            $wasEdit ? 'visit_report_edited' : 'visit_report_created',
            $wasEdit ? 'activity_log.visit_report_edited' : 'activity_log.visit_report_created',
            ['patient' => $booking->user->name]
        );

        $report = VisitReport::where('booking_id', $booking->id)->with('medications')->first();
        $booking->user->notify(new VisitReportCompleted($report));

        return back()->with('success', __('doctor.report_modal.save_success'));
    }
}
