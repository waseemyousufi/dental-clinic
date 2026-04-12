<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Allergy;
use Illuminate\Http\Request;

class AllergyController extends Controller
{
    public function store(Request $request) {

        $data = $request->validate([
            
        ]);

        Allergy::create([

        ]);
    }
}
