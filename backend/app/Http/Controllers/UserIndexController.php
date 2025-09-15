<?php

// namespace App\Http\Controllers\Admin;
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserIndexController extends Controller
{
    // GET /api/admin/users
    public function index()
    {
        // Listado simple para el panel admin (módulo clásico, sin obra)
        $users = User::query()
            ->select('id','name','email','created_at')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }
}
