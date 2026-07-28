<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * محاولة تسجيل الدخول — تقبل البريد الإلكتروني أو الرقم الجامعي/الوظيفي
     * بنفس الخانة، وتحوّلها داخليًا لبريد المستخدم الحقيقي للمصادقة.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim((string) $this->input('login'));

        $email = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? $login
            : optional(User::where('identifier', $login)->first())->email;

        if (!$email || !Auth::attempt(['email' => $email, 'password' => $this->input('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        // تحقق إضافي: الحساب لازم يكون مفعّل
        if (!Auth::user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'login' => 'تم تعطيل هذا الحساب. الرجاء التواصل مع إدارة العيادة.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => "عدد المحاولات كبير جدًا. حاول مرة أخرى خلال {$seconds} ثانية.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}