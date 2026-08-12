<?php

namespace Database\Seeders;

use App\Models\DoctorDayAssignment;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * توزيع افتراضي لأيام دوام العيادة (الأحد–الخميس) على الأطباء الثلاثة
 * المزروعين بـUserSeeder — مثال توضيحي بسيط (أحد+اثنين لطبيب، ثلاثاء+أربعاء
 * لآخر، خميس للثالث) قابل للتعديل لاحقًا من لوحة الإدارة (admin.day-assignments)،
 * وليس ثابتًا بالكود.
 *
 * يُزامن working_days كل طبيب مع أيامه المُعيَّنة هنا تحديدًا (updateOrCreate
 * على DoctorSchedule) كي لا تظهر بيانات العرض التجريبية بتنبيه "تعارض" فور
 * الزرع — الجدولان يبقيان مستقلَّين بعد ذلك؛ أي تعديل لاحق من لوحة الإدارة
 * (توزيع الأيام، أو صفحة تعديل حساب الدكتور) يقدر يُحدث أحدهما بلا الآخر.
 */
class DoctorDayAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $doctorA = User::where('identifier', '111')->first();
        $doctorB = User::where('identifier', '222')->first();
        $doctorC = User::where('identifier', '333')->first();

        if (! $doctorA || ! $doctorB || ! $doctorC) {
            return;
        }

        // [رقم يوم الأسبوع بمعيار Carbon::dayOfWeek, الطبيب]
        $plan = [
            [0, $doctorA], // الأحد
            [1, $doctorA], // الاثنين
            [2, $doctorB], // الثلاثاء
            [3, $doctorB], // الأربعاء
            [4, $doctorC], // الخميس
        ];

        foreach ($plan as [$dayOfWeek, $doctor]) {
            DoctorDayAssignment::updateOrCreate(
                ['day_of_week' => $dayOfWeek],
                ['doctor_id' => $doctor->id],
            );
        }

        foreach ([$doctorA, $doctorB, $doctorC] as $doctor) {
            $workingDays = collect($plan)
                ->filter(fn (array $row) => $row[1]->id === $doctor->id)
                ->map(fn (array $row) => $row[0])
                ->values()
                ->all();

            DoctorSchedule::updateOrCreate(
                ['doctor_id' => $doctor->id],
                ['working_days' => $workingDays],
            );
        }
    }
}
