<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    use hasFactory;
    protected $table = 'shifts';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'state'
    ];
}
