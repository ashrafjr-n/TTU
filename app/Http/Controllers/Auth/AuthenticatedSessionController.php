<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view — يحمل نوع الحساب من الرابط (إن وُجد) فقط لأغراض
     * العرض (شارة الدور واسم حقل الدخول)، الدخول نفسه غير مقيد بالدور:
     * LoginRequest يقبل الرقم أو البريد لأي دور كان. أي قيمة خارج القائمة
     * (أو دخول مباشر على /login بلا معامل) تُعامَل كـ null فتظهر الصياغة
     * العامة كما كانت.
     */
    public function create(Request $request): View
    {
        $role = $request->query('role');

        if (!in_array($role, ['student', 'staff', 'doctor', 'admin'], true)) {
            $role = null;
        }

        return view('auth.login', ['role' => $role]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
