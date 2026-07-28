<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // طالب تجريبي
        User::create([
            'name' => 'أحمد خالد',
            'email' => 'student@ttu.edu.jo',
            'password' => Hash::make('password'),
            'role' => 'student',
            'identifier' => '20210123',
        ]);

        // موظف تجريبي
        User::create([
            'name' => 'محمد علي',
            'email' => 'staff@ttu.edu.jo',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'identifier' => '2320',
        ]);

        // ثلاثة حسابات دكاترة ثابتة فقط — لا يوجد تسجيل عام لهذا الدور
        $doctors = [
            ['name' => 'د. أشرف جرابعة', 'email' => 'doctor-1@ttu.edu.jo'],
            ['name' => 'د. سارة يوسف',   'email' => 'doctor-2@ttu.edu.jo'],
            ['name' => 'د. خالد ناصر',   'email' => 'doctor-3@ttu.edu.jo'],
        ];

// حساب المدير الثابت — لا يوجد تسجيل عام لهذا الدور
User::create([
    'name' => 'إدارة عيادة TTU',
    'email' => 'admin@ttu.edu.jo',
    'password' => Hash::make('password'),
    'role' => 'admin',
    'identifier' => 'admin@ttu.edu.jo',
]);

        foreach ($doctors as $doctor) {
            User::create([
                'name' => $doctor['name'],
                'email' => $doctor['email'],
                'password' => Hash::make('password'),
                'role' => 'doctor',
                'identifier' => $doctor['email'],
            ]);
        }
    }
}