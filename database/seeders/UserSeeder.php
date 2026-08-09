<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // لا يوجد تسجيل ذاتي — كل رقم جامعي/وظيفي مصرّح به (UniversityRecordSeeder)
        // لازم يقابله هنا حساب مستخدم جاهز فعليًا، وإلا ما في طريقة لأصحابه يدخلوا.
        $students = [
            ['identifier' => '20210123', 'name' => 'أحمد خالد',   'email' => 'student@ttu.edu.jo'],
            ['identifier' => '20210456', 'name' => 'سارة عبدالله', 'email' => 'student-2@ttu.edu.jo'],
            ['identifier' => '20210789', 'name' => 'عمر ياسين',    'email' => 'student-3@ttu.edu.jo'],
            ['identifier' => '20210999', 'name' => 'لمى حسن',      'email' => 'student-4@ttu.edu.jo'],
            ['identifier' => '20210555', 'name' => 'يوسف مراد',    'email' => 'student-5@ttu.edu.jo'],
            ['identifier' => '00000000', 'name' => 'رنا فؤاد',     'email' => 'student-6@ttu.edu.jo'],
            ['identifier' => '11111111', 'name' => 'كريم سامي',    'email' => 'student-7@ttu.edu.jo'],
            ['identifier' => '22222222', 'name' => 'ديما نمر',     'email' => 'student-8@ttu.edu.jo'],
        ];

        foreach ($students as $student) {
            User::firstOrCreate(
                ['email' => $student['email']],
                [
                    'name' => $student['name'],
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'identifier' => $student['identifier'],
                ],
            );
        }

        $staffMembers = [
            ['identifier' => '2320', 'name' => 'محمد علي', 'email' => 'staff@ttu.edu.jo'],
            ['identifier' => '4491', 'name' => 'هبة سالم',  'email' => 'staff-2@ttu.edu.jo'],
            ['identifier' => '7758', 'name' => 'زياد قاسم', 'email' => 'staff-3@ttu.edu.jo'],
            ['identifier' => '0000', 'name' => 'نور إبراهيم', 'email' => 'staff-4@ttu.edu.jo'],
            ['identifier' => '1111', 'name' => 'باسل عودة',   'email' => 'staff-5@ttu.edu.jo'],
            ['identifier' => '2222', 'name' => 'ريم صالح',    'email' => 'staff-6@ttu.edu.jo'],
        ];

        foreach ($staffMembers as $staff) {
            User::firstOrCreate(
                ['email' => $staff['email']],
                [
                    'name' => $staff['name'],
                    'password' => Hash::make('password'),
                    'role' => 'staff',
                    'identifier' => $staff['identifier'],
                ],
            );
        }

        // حساب المدير الثابت — لا يوجد تسجيل عام لهذا الدور
        User::firstOrCreate(
            ['email' => 'admin@ttu.edu.jo'],
            [
                'name' => 'إدارة عيادة TTU',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'identifier' => 'admin@ttu.edu.jo',
            ],
        );

        // ثلاثة حسابات دكاترة ثابتة فقط — لا يوجد تسجيل عام لهذا الدور.
        // identifier صار رقمًا وظيفيًا من 3 خانات (بدل البريد سابقًا) —
        // updateOrCreate هنا عمدًا (لا firstOrCreate) كي يُصحَّح identifier
        // القديم (بريد) على أي قاعدة بيانات سبق زرعها بالنسخة السابقة.
        $doctors = [
            ['name' => 'د. أشرف جرابعة', 'email' => 'doctor-1@ttu.edu.jo', 'identifier' => '000'],
            ['name' => 'د. سارة يوسف',   'email' => 'doctor-2@ttu.edu.jo', 'identifier' => '111'],
            ['name' => 'د. خالد ناصر',   'email' => 'doctor-3@ttu.edu.jo', 'identifier' => '222'],
        ];

        foreach ($doctors as $doctor) {
            User::updateOrCreate(
                ['email' => $doctor['email']],
                [
                    'name' => $doctor['name'],
                    'password' => Hash::make('password'),
                    'role' => 'doctor',
                    'identifier' => $doctor['identifier'],
                ],
            );
        }
    }
}
