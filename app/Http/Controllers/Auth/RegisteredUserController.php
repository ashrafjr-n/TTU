<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UniversityRecord;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * الأدوار المسموح تسجيلها ذاتيًا — الدكتور مستثنى عمدًا،
     * حساباته الثلاثة ثابتة ومزروعة مسبقًا عبر Seeder فقط.
     */
    private const REGISTRABLE_ROLES = ['student', 'staff'];

    /**
     * عرض صفحة التسجيل — يشترط تحديد دور صالح (طالب/موظف) مسبقًا
     */
    public function create(Request $request): View|RedirectResponse
    {
        $role = $request->query('role');

        if (!in_array($role, self::REGISTRABLE_ROLES, true)) {
            return redirect(route('home').'#roles')
                ->with('error', __('auth_forms.register.errors.invalid_role'));
        }

        return view('auth.register', ['role' => $role]);
    }

    /**
     * تنفيذ عملية التسجيل
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $role = $request->input('role');

        if (!in_array($role, self::REGISTRABLE_ROLES, true)) {
            return redirect(route('home').'#roles')
                ->with('error', __('auth_forms.register.errors.role_error'));
        }

        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $digitsRule = $role === 'student' ? 'digits:8' : 'digits:4';
        $identifierLabel = __('auth_forms.register.identifier_label.'.$role);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:student,staff',
            'identifier' => ['required', $digitsRule, 'unique:'.User::class.',identifier'],
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'identifier.required' => __('auth_forms.register.errors.identifier_required', ['label' => $identifierLabel]),
            'identifier.digits' => $role === 'student'
                ? __('auth_forms.register.errors.student_id_digits')
                : __('auth_forms.register.errors.staff_id_digits'),
            'identifier.unique' => __('auth_forms.register.errors.identifier_taken', ['label' => $identifierLabel]),
            'email.unique' => __('auth_forms.register.errors.email_taken'),
            'password.confirmed' => __('auth_forms.register.errors.password_confirmed'),
        ]);

        // التحقق من الرقم عبر قاعدة بيانات الجامعة الوهمية
        $record = UniversityRecord::where('identifier', $validated['identifier'])
            ->where('type', $role)
            ->where('is_valid', true)
            ->first();

        if (!$record) {
            return back()
                ->withErrors(['identifier' => __('auth_forms.register.errors.identifier_not_found')])
                ->withInput();
        }

        $user = User::create([
            'name' => $validated['name'],
            'role' => $role,
            'identifier' => $validated['identifier'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // إعادة توليد الجلسة بعد تسجيل الدخول التلقائي (مطابقة لما يحصل عند
        // تسجيل الدخول العادي) لمنع session fixation.
        $request->session()->regenerate();

        return redirect()->route('dashboard.'.$role);
    }
}