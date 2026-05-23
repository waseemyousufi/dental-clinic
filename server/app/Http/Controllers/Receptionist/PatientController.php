<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        $patients = Patient::where('branch_id', $branchId)->get();
        return PatientResource::collection($patients);
    }

    public function show(string $id)
    {
        return new PatientResource(Patient::findOrFail($id));
    }

    public function setAllergy(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'allergyType' => 'string|required',
            'severity' => 'string|required',
            'description' => 'string|required',
            'patientId' => 'integer|required',
        ]);

        $patient = Patient::find($data['patientId']);

        return $patient->allergy->save([
            'allergy_type' => $data['allergyType'],
            'severity' => $data['severity'],
            'description' => $data['description'],
            'branch_id' => $branchId,
        ]);
    }

    public function setDebit(Request $request, String $id)
    {
        $branchId = $this->effectiveBranchId($request);

        $data = $request->validate([
            'debit' => 'integer|required',
        ]);

        $patient = Patient::find($id);
        $account = Account::where('branch_id', $branchId)->where('account_type', 'income')->first();

        DB::transaction(function () use ($patient, $account, $data, $branchId, $request) {



            $account->update([
                'total_amount' => $account->total_amount + $data['debit'],
                'branch_id' => $branchId
            ]);

            AccountTransaction::create([
                'transaction_type' => 'in',
                'amount' => $data['debit'],
                'transaction_date' => now(),
                'reference_type' => 'patient',
                'description' => 'Collected from ' . $patient->f_name . ' ' . $patient->l_name,
                'recorded_by_employee_id' => $request->user()->id,
                'account_id' => 1,
                'branch_id' => $branchId
            ]);

            $patient->total_amount_due = $patient->total_amount_due - $data['debit'];
            $patient->save();

            return [
                'totalAmountDue' => $patient->total_amount_due
            ];
        });
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
            'gender' => 'required|string',
            'bloodType' => 'required|string',
            'emgContact' => 'nullable|string',
            'registerationDate' => 'required|string',
            'phone' => 'required|string',
            'reception_cost' => 'required|integer',
        ]);

        DB::transaction(function () use ($data, $branchId, $request) {
            if ($data['reception_cost'] > 0) {
                Account::where('branch_id', $branchId)
                    ->where('account_type', 'income')
                    ->first()
                    ->increment('total_amount', $data['reception_cost']);
                AccountTransaction::create([
                    'transaction_type' => 'in',
                    'amount' => $data['reception_cost'],
                    'transaction_date' => now(),
                    'reference_type' => 'patient',
                    'description' => 'Reception fee for new patient: ' . $data['fName'] . ' ' . $data['lName'],
                    'recorded_by_employee_id' => $request->user()->id,
                    'account_id' => 1,
                    'branch_id' => $branchId
                ]);
            }

            return Patient::create([
                'f_name' => $data['fName'],
                'l_name' => $data['lName'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'blood_type' => $data['bloodType'],
                'emergency_contact' => $data['emgContact'],
                'registeration_date' => $data['registerationDate'],
                'branch_id' => $branchId,
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
            'gender' => 'required|string',
            'bloodType' => 'required|string',
            'emgContact' => 'nullable|string',
            'registerationDate' => 'required|string',
            'phone' => 'required|string',
        ]);

        return Patient::find($id)->update([
            'f_name' => $data['fName'],
            'l_name' => $data['lName'],
            'gender' => $data['gender'],
            'phone' => $data['phone'],
            'blood_type' => $data['bloodType'],
            'emergency_contact' => $data['emgContact'],
            'registeration_date' => $data['registerationDate'],
            'branch_id' => $branchId,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return Patient::find($id)->delete();
    }
}
