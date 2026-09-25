<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\User;
use App\Models\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            // Pending user → log them out, send a fresh OTP, redirect to verify
            if ($user->status === 'pending') {
                Auth::logout();

                // Generate and save a fresh OTP
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->update([
                    'otp'            => $otp,
                    'otp_expires_at' => now()->addMinutes(10),
                ]);

                if (!$this->sendOtp($user, $otp)) {
                    return back()->withInput($request->only('email'))
                        ->withErrors(['email' => "We couldn't send a verification code to {$user->email}. Please try again later."]);
                }

                session(['otp_email' => $user->email]);

                return redirect()->route('otp.show')
                    ->with('success', 'A verification code has been sent to your email.');
            }

            if ($user->role && $user->role->name === 'admin') {
                return redirect('/admin/dashboard');
            }
            return redirect()->intended('/');
        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    // Step 1: Validate form, save pending user, send OTP
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required',
            // An address that registered but never verified can register again
            'email'    => ['required', 'email', Rule::unique('users')->where(fn ($q) => $q->where('status', '!=', 'pending'))],
            'password' => 'required|min:6|confirmed',
        ]);

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $customerRole = Roles::firstOrCreate(['name' => 'customer']);

        // Only keep the account if the code actually went out
        DB::beginTransaction();

        $user = User::updateOrCreate(
            ['email' => $request->email, 'status' => 'pending'],
            [
                'name'           => $request->name,
                'password'       => Hash::make($request->password),
                'role_id'        => $customerRole->id,
                'otp'            => $otp,
                'otp_expires_at' => now()->addMinutes(10),
            ]
        );

        if ($this->sendOtp($user, $otp)) {
            DB::commit();
        } else {
            DB::rollBack();

            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => "We couldn't send a verification code to {$request->email}. Please check the address or try again later."]);
        }

        session(['otp_email' => $request->email]);

        return redirect()->route('otp.show')->with('success', 'OTP sent to your email!');
    }

    /**
     * Email the verification code. Returns false if it couldn't be sent.
     *
     * In local development, a failed send (e.g. Resend only delivers to the account
     * owner until a domain is verified) is not fatal: the code is written to
     * storage/logs/laravel.log so the flow can still be tested.
     */
    private function sendOtp(User $user, string $otp): bool
    {
        try {
            Mail::to($user->email)->send(new OtpMail($otp));
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send OTP email', ['email' => $user->email, 'error' => $e->getMessage()]);

            if (app()->isLocal()) {
                Log::info("Local dev: OTP for {$user->email} is {$otp}");
                session()->flash('info', "Email couldn't be sent (" . Str::limit($e->getMessage(), 120) . '). Local development: your code is in storage/logs/laravel.log.');
                return true;
            }

            return false;
        }
    }

    // Step 2: Show OTP input page
    public function showOtp()
    {
        if (!session('otp_email')) {
            return redirect()->route('register');
        }
        return view('auth.otp');
    }

    // Step 3: Verify OTP
    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $email = session('otp_email');
        $user  = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors(['otp' => 'Session expired. Please register again.']);
        }

        $enteredOtp = (string) trim($request->otp);
        $storedOtp  = (string) trim($user->otp ?? '');

        if ($storedOtp === '' || $storedOtp !== $enteredOtp) {
            return back()->withErrors(['otp' => 'Invalid OTP. Please try again.']);
        }

        if (now()->isAfter($user->otp_expires_at)) {
            return back()->withErrors(['otp' => 'OTP has expired. Please register again.']);
        }

        // ✅ Clear OTP and activate the account
        $user->update([
            'otp'            => null,
            'otp_expires_at' => null,
            'status'         => 'active',
        ]);

        Auth::login($user);
        session()->forget('otp_email');

        return redirect('/')->with('success', 'Email verified! Welcome to CineMax.');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
}