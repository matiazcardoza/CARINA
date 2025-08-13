<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkEvidence extends Model
{
    use HasFactory;

    protected $table = 'work_evidence';
    protected $primaryKey = 'id';

    protected $fillable = [
        'daily_part_id',
        'evidence_path',
    ];
}
