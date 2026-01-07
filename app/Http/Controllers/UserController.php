<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id', 'desc')->get();

        $roleLabel = [
            'admin'  => 'Administrator',
            'staff'  => 'Staff Pemasaran',
            'viewer' => 'Viewer',
        ];

        // sesuai struktur kamu: resources/views/dashboard/users.blade.php
        return view('dashboard.users', compact('users', 'roleLabel'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', Rule::in(['admin', 'staff', 'viewer'])],
            'status'   => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role'     => ['required', Rule::in(['admin', 'staff', 'viewer'])],
            'status'   => ['required', Rule::in(['active', 'inactive'])],
        ]);

        // ✅ Khusus Administrator SISIR: role tidak boleh berubah
        if ($user->username === 'admin') {
            $data['role'] = 'admin';
        }


        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user)
    {
        // Fix Intelephense: pakai Auth facade (bukan auth()->check() / auth()->id())
        if (Auth::check() && Auth::id() === $user->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
