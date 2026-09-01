<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Check if the user is authorized to manage users.
     * Allowed roles: admin, doctor
     */
    private function authorizeAdmin()
    {
        $role = strtolower(auth()->user()->role ?? '');
        if (!in_array($role, ['admin', 'administrador', 'doctor'])) {
            abort(403, 'No tienes permiso para gestionar usuarios.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();
        $query = User::orderBy('name');

        if ($request->has('trashed') && $request->trashed == 'true') {
            $query->onlyTrashed();
        } else if ($request->has('all') && $request->all == 'true') {
            $query->withTrashed();
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|string',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        return response()->json($user);
    }

    public function destroy($id)
    {
        $this->authorizeAdmin();

        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta.'], 403);
        }

        $user->delete(); // Soft delete because of the trait in the model
        return response()->json(['message' => 'Usuario eliminado temporalmente']);
    }

    public function restore($id)
    {
        $this->authorizeAdmin();

        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return response()->json(['message' => 'Usuario restaurado correctamente']);
    }

    public function forceDelete($id)
    {
        $this->authorizeAdmin();

        $user = User::withTrashed()->findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'No puedes eliminar definitivamente tu propia cuenta.'], 403);
        }

        $user->forceDelete();
        return response()->json(['message' => 'Usuario eliminado definitivamente']);
    }
}
