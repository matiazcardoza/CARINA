<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentDailyPart extends Model
{
    use HasFactory;

    protected $table = 'documents_daily_parts';
    protected $primaryKey = 'id';

    protected $fillable = [
        'file_path',
        'state'
    ];
}
