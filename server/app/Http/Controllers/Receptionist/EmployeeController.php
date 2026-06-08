<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeSalaryResource;
use App\Models\Account;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\info;
use App\Models\AccountTransaction;
use App\Models\EmployeeSalary;
use PhpParser\Node\Stmt\TryCatch;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index(Request $request)
{
    $branchId = $this->effectiveBranchId($request);

    $employees = Employee::with(['user', 'Position'])
        ->where('branch_id', $branchId)
        ->whereHas('Position', function ($query) {
            $query->where('position_title', '!=', 'admin');
        })
        ->get();

    return EmployeeResource::collection($employees);
}

    public function updateProfliePic(String $id, Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048', // 2MB Max
        ]);

        $branchId = $this->effectiveBranchId($request);
        $employee = Employee::where('branch_id', $branchId)->findOrFail($id);
        $user = $employee->user;

        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
        }

        $path = $request->file('image')->store('profile_images', 'public');
        $user->profile_image_path = $path;
        $user->save();

        return response()->json(['message' => 'Profile picture updated successfully']);
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
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:30',
                'gender' => 'required',
                'hireDate' => 'required|date',
                'speciality' => 'required|string',
                'qualification' => 'required|string',
                'midLicenseNum' => 'required|string',
                'workStartTime' => 'required|date_format:H:i',
                'workEndTime' => 'required|date_format:H:i',
                'positionId' => 'required|exists:positions,id',
                'experience.workplace' => 'nullable|string',
                'experience.position' => 'nullable|string',
                'experience.totalAmount' => 'nullable|integer'
                ]);

        return DB::transaction(function () use ($data, $request, $branchId) {
            $user = User::create([
                'name' => $data['fName'] . " " . $data['lName'],
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
                'phone' => $data['phone'] ?? null,
                'work_start_time' => $data['workStartTime'],
                'work_end_time' => $data['workEndTime'],
                'branch_id' => $branchId,
                'position_id' => $data['positionId'],
                'branch_id' => $branchId,
            ]);

            if ($data['experience']) {
                $employee->experience()->create([
                    'workplace' => $data['experience']['workplace'],
                    'position' => $data['experience']['position'],
                    'total_amount' => $data['experience']['totalAmount'],
                    'branch_id' => $branchId,
                ]);
            }

            $token = Password::createToken($user);
            return response(['token' => $token, 'email' => $user->email, 'employeeId' => $employee->id], 201);
        });
    }

    public function getSalaries(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $salaries = EmployeeSalary::with('AccountTransaction.Account')->with('employee')
            ->whereHas('employee', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->latest()
            ->get();

        return EmployeeSalaryResource::collection($salaries);
    }

    public function employeeSalary(string $id, Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $data = $request->validate([
            'salaryMonth' => 'required|string',
            'amount' => 'required|integer',
            'bonus' => 'required|integer',
            'totalAmount' => 'required|integer',
            'remark' => 'nullable|string',
            'accountId' => 'required|integer',
        ]);

        $employee = Employee::where('branch_id', $branchId)->findOrFail($id);

        return DB::transaction(function () use ($employee, $data, $request, $branchId) {
            $account = Account::where('branch_id', $branchId)->findOrFail($data['accountId']);

            if ($account->total_amount < $data['totalAmount']) {
                abort(400, 'Insufficient account balance');
            }

            $account->total_amount -= $data['totalAmount'];
            $account->save();

            // 1. CREATE TRANSACTION FIRST
            // Use the authenticated user's employee id (not the user id) to satisfy FK constraint
            $recordedByEmployeeId = null;
            if (auth()->check()) {
                $recordedByEmployeeId = auth()->user()?->employee?->id ?? null;
            }

            $transaction = AccountTransaction::create([
                'transaction_type' => 'out',
                'amount' => $data['totalAmount'],
                'transaction_date' => now(),
                'reference_type' => 'employee_salary',
                'description' => "Salary payment for {$employee->f_name} {$employee->l_name}",
                'recorded_by_employee_id' => $recordedByEmployeeId,
                'account_id' => $data['accountId'],
                'branch_id' => $branchId,
            ]);

            // 2. CREATE / UPSERT SALARY
            $employee->salaries()->create(
                [
                    'salary_month' => $data['salaryMonth'],
                    'amount' => $data['amount'],
                    'bonus' => $data['bonus'],
                    'total_amount' => $data['totalAmount'],
                    'remark' => $data['remark'],
                    'paidByAccountTransaction_id' => $transaction->id,
                    'branch_id' => $this->effectiveBranchId($request)
                ]
            );

            return response()->json([
                'message' => 'Salary paid successfully',
                'transactionId' => $transaction->id,
            ]);
        });
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
            'phone' => 'nullable|string|max:30',
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
            $user = User::with(['employee.experience'])
                ->whereHas('employee', fn($q) => $q->where('branch_id', $branchId))
                ->findOrFail($id);

            if ($user->employee) {
                $user->employee()->update([
                    'f_name' => $data['fName'],
                    'l_name' => $data['lName'],
                    'gender' => $data['gender'],
                    'hire_date' => $data['hireDate'],
                    'qualification' => $data['qualification'],
                    'speciality' => $data['speciality'],
                    'medical_license_number' => $data['midLicenseNum'],
                    'phone' => $data['phone'] ?? null,
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
        $branchId = $this->effectiveBranchId(request());
        $emp = Employee::where('branch_id', $branchId)->findOrFail($id);
        $emp->user()->delete();
        $emp->delete();
    }
}
