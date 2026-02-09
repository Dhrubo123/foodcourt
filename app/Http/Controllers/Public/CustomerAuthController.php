<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CustomerAuthController extends Controller
{
    public function showRegister()
    {
        return view('customer-register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone', 'required_without:email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => 'active',
            ]);

            $role = Role::query()->firstOrCreate([
                'name' => 'customer',
                'guard_name' => 'sanctum',
            ]);

            if (! $user->hasRole($role->name)) {
                $user->assignRole($role->name);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('customer.account');
    }

    public function showLogin()
    {
        return view('customer-login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $data['login'])
            ->orWhere('phone', $data['login'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors([
                'login' => 'Invalid credentials.',
            ])->withInput();
        }

        if ($user->status !== 'active') {
            return back()->withErrors([
                'login' => 'Account is not active.',
            ])->withInput();
        }

        if (! $user->hasRole('customer')) {
            return back()->withErrors([
                'login' => 'This login page is for customer accounts only.',
            ])->withInput();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('customer.account');
    }

    public function account(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('customer')) {
            Auth::logout();

            return redirect()->route('customer.login');
        }

        return view('customer-account', [
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}

