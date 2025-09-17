<?php

namespace App\Http\Controllers;

// namespace App\Http\Controllers\Admin;

// use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Obra;
use Spatie\Permission\Models\Role;

class AdminCatalogController extends Controller
{
    public function obras()
    {
        return Obra::query()->orderBy('nombre')->get(['id','nombre','codigo']);
    }

    public function roles()
    {
        // roles definidos (Spatie). Si usas guards distintos, filtra por guard_name
        return Role::query()->orderBy('name')->get(['id','name','guard_name']);
    }
}
