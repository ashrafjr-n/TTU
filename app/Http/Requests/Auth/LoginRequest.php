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