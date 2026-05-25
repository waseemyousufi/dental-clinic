<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

// [ ] make the controller work just fine

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::with(['user', 'Position'])
            ->whereHas('Position', function ($query) {
                $query->where('position_title', '!=', 'admin');
            })
            ->get();

        return EmployeeResource::collection($employees);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $data = $request->validate([
            'name' => 'required|string',
            'fName' => 'required|string',
            'lName' => 'required|string',
            'email' => 'required|unique:users,email',
            'gender' => 'required', // , new Enum(Gender::class)],
            'hireDate' => 'required|string',
            'speciality' => 'required|string',
            'qualification' => 'required|string',
            'midLicenseNum' => 'required|string',
            'workStartTime' => 'required|string',
            'workEndTime' => 'required|string',
            'branchId' => 'required|integer',
            'positionId' => 'required|integer',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name']. " ". $data['lName'],
                'password' => Hash::make('temp_pass'),
                'email' => $data['email'],
            ]);

            $user->employee()->create([
                'f_name' => $data['fName'],
                'l_name' => $data['lName'],
                'gender' => $data['gender'],
                'hire_date' => $data['hireDate'],
                'qualification' => $data['qualification'],
                'speciality' => $data['speciality'],
                'medical_license_number' => $data['midLicenseNum'],
                'work_start_time' => $data['workStartTime'],
                'work_end_time' => $data['workEndTime'],
                'branch_id' => $data['branchId'],
                'position_id' => $data['positionId'],
            ]);

            Auth::login($user, true);

            $token = Password::createToken($user);

            return response(['token' => $token, 'email' => $user->email], 201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
