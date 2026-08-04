<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'identifier',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // العلاقات
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // علاقات خاصة بالدكتور فقط، لكنها متاحة على User عمومًا (role='doctor')
    public function doctorSchedule(): HasOne
    {
        return $this->hasOne(DoctorSchedule::class, 'doctor_id');
    }

    public function doctorAttendance(): HasMany
    {
        return $this->hasMany(DoctorAttendance::class, 'doctor_id');
    }

    public function visitReportsAsDoctor(): HasMany
    {
        return $this->hasMany(VisitReport::class, 'doctor_id');
    }

    // Helper methods مفيدة لاحقًا بالكود (تسهل قراءة الشروط)
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * الحرف الأول من الاسم — تُستخدم لأفتار المستخدم في القوائم الصغيرة
     * (سجل الحضور، إدارة المستخدمين). أسماء الأطباء مخزَّنة بلقب بادئ
     * ("د. أشرف جرابعة")، فلا بد من تجريد اللقب أولًا وإلا صار الحرف
     * المعروض "د" (اللقب) بدل "أ" (أول حرف من الاسم الفعلي).
     *
     * النقطة بعد "د" إلزامية في النمط — أسماء حقيقية تبدأ بحرف الدال
     * ("داود"، "دانا") لا تُكتب بنقطة بعده أبدًا، فاشتراطها يمنع خلطها
     * باللقب.
     */
    public function nameInitial(): string
    {
        $name = preg_replace('/^د\.\s*/u', '', $this->name);

        return mb_substr($name, 0, 1);
    }
}