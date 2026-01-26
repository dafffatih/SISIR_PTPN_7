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

        return view('dashboard.users', compact('users', 'roleLabel'));
    }

    public function store(Request $request)
    {
        // 1. Definisikan pesan error bahasa Indonesia
        $messages = [
            'required'   => ':attribute wajib diisi.',
            'string'     => ':attribute harus berupa teks.',
            'max'        => ':attribute maksimal :max karakter.',
            'min'        => ':attribute minimal :min karakter.',
            'unique'     => ':attribute sudah digunakan, silakan ganti yang lain.',
            'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, strip (-), dan underscore (_).',
            'in'         => 'Pilihan :attribute tidak valid.',
        ];

        // 2. Definisikan nama atribut agar lebih manusiawi
        $attributes = [
            'name'     => 'Nama Lengkap',
            'username' => 'Username',
            'password' => 'Password',
            'role'     => 'Role',
            'status'   => 'Status',
        ];

        // 3. Masukkan $messages dan $attributes ke fungsi validate
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', Rule::in(['admin', 'staff', 'viewer'])],
            'status'   => ['required', Rule::in(['active', 'inactive'])],
        ], $messages, $attributes);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        // Sama seperti store, kita gunakan pesan bahasa Indonesia
        $messages = [
            'required'   => ':attribute wajib diisi.',
            'string'     => ':attribute harus berupa teks.',
            'max'        => ':attribute maksimal :max karakter.',
            'min'        => ':attribute minimal :min karakter.',
            'unique'     => ':attribute sudah digunakan, silakan pilih yang lain.',
            'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, strip (-), dan underscore (_).',
            'in'         => 'Pilihan :attribute tidak valid.',
        ];

        $attributes = [
            'name'     => 'Nama Lengkap',
            'username' => 'Username',
            'password' => 'Password',
            'role'     => 'Role',
            'status'   => 'Status',
        ];

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role'     => ['required', Rule::in(['admin', 'staff', 'viewer'])],
            'status'   => ['required', Rule::in(['active', 'inactive'])],
            'user_id'  => ['nullable'], // Field hidden helper
        ], $messages, $attributes);

        // Logic Administrator SISIR (Hardcode protection)
        if ($user->username === 'admin') {
            $data['role'] = 'admin';
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['user_id']); // Hapus sebelum simpan ke DB

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user)
    {
        if (Auth::check() && Auth::id() === $user->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}