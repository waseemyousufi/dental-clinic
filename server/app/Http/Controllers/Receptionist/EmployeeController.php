<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

use function Laravel\Prompts\info;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        info('Effective Branch ID: ' . $branchId);
        $employees = Employee::where('branch_id', $branchId)->get();
        return EmployeeResource::collection($employees);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
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
            'positionId' => 'required',
            'experience.workplace' => 'string',
            'experience.position' => 'string',
            'experience.totalAmount' => 'integer'
        ]);

        return DB::transaction(function () use ($data, $request, $branchId) {
            $user = User::create([
                'name' => $data['fName'] . " ". $data['lName'],
                'password' => Hash::make('temp_pass'),
                'email' => $data['email'],
            ]);

            $employee = $user->employee()->create([
                'f_name' => $data['fName'],
                'l_name' => $data['lName'],
                'gender' => $data['gender'],
                'hire_date' => $data['hireDate'],
                'qualification' => $data['qualification'],
                'speciality' => $data['speciality'],
                'medical_license_number' => $data['midLicenseNum'],
                'work_start_time' => $data['workStartTime'],
                'work_end_time' => $data['workEndTime'],
                'branch_id' => $branchId,
                'position_id' => $data['positionId'],
            ]);


            if ($data['experience']) {
                $employee->experience()->create([
                    'workplace' => $data['experience']['workplace'],
                    'position' => $data['experience']['position'],
                    'total_amount' => $data['experience']['totalAmount'],
                ]);
            }

            $token = Password::createToken($user);
            return response(['token' => $token, 'email' => $user->email], 201);
        });
    }

    public function employeeSalary(Request $request, string $id)
    {

        $data = $request->validate([
            'salaryMonth' => 'string|required',
            'amount' => 'integer|required',
            'bonus' => 'integer|required',
            'totalAmount' => 'integer|required',
            'remark' => 'string|required',
            'transactionId' => 'integer|required',
        ]);

        return Employee::find($id)->EmployeeSalary()->upsert(
            [
                'salary_month' => $data['salaryMonth'],
                'amount' => $data['amount'],
                'bonus' => $data['bonus'],
                'total_amount' => $data['totalAmount'],
                'remark' => $data['remark'],
                'paidByAccountTransaction_id' => $data['transactionId'],
            ],
            [
                'salary_month' => $data['salaryMonth'],
                'amount' => $data['amount'],
                'bonus' => $data['bonus'],
                'total_amount' => $data['totalAmount'],
                'remark' => $data['remark'],
                'paidByAccountTransaction_id' => $data['transactionId'],
            ],
            [
                'salary_month' => $data['salaryMonth'],
                'amount' => $data['amount'],
                'bonus' => $data['bonus'],
                'total_amount' => $data['totalAmount'],
                'remark' => $data['remark'],
                'paidByAccountTransaction_id' => $data['transactionId'],
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'fName' => 'required|string',
            'lName' => 'required|string',
            'gender' => 'required',
            'hireDate' => 'required|string',
            'speciality' => 'required|string',
            'qualification' => 'required|string',
            'midLicenseNum' => 'required|string',
            'workStartTime' => 'required|string',
            'workEndTime' => 'required|string',
            'positionId' => 'required|integer',
            'experience.workplace' => 'string',
            'experience.position' => 'string',
            'experience.totalAmount' => 'integer'
        ]);

        return DB::transaction(function () use ($data, $request, $id, $branchId) {
            $user = User::with(['employee.experience'])->findOrFail($id);

            if ($user->employee) {
                $user->employee()->update([
                    'f_name' => $data['fName'],
                    'l_name' => $data['lName'],
                    'gender' => $data['gender'],
                    'hire_date' => $data['hireDate'],
                    'qualification' => $data['qualification'],
                    'speciality' => $data['speciality'],
                    'medical_license_number' => $data['midLicenseNum'],
                    'work_start_time' => $data['workStartTime'],
                    'work_end_time' => $data['workEndTime'],
                    'branch_id' => $branchId,
                    'position_id' => $data['positionId'],
                ]);

                if ($user->employee->experience?->workplace != null) {
                    $user->employee->experience()->update([
                        'workplace' => $data['experience']['workplace'],
                        'position' => $data['experience']['position'],
                        'total_amount' => $data['experience']['totalAmount']
                    ]);
                } else {
                    $user->employee->experience()->create([
                        'workplace' => $data['experience']['workplace'],
                        'position' => $data['experience']['position'],
                        'total_amount' => $data['experience']['totalAmount']
                    ]);
                }
            }

            $user->push();
        });
    }

    public function delete(string $id)
    {
        $emp = Employee::find($id);
        $emp->user()->delete();
        $emp->delete();
    }
}
