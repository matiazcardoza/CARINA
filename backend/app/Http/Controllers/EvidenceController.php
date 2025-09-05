<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use Illuminate\Http\Request;

class EvidenceController extends Controller
{
    function getEvedence($serviceId){
        $dailyPart = DailyPart::where('service_id', $serviceId)->get();
    }
}
