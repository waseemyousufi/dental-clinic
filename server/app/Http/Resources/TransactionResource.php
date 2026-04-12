<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $emp = new EmployeeResource($this->employee);
        $name = $emp->f_name . " " . $emp->l_name;

        return [
            'id' => $this->id,
            'transactionType' => $this->transaction_type,
            'amount' => $this->amount,
            'transactionDate' => $this->transaction_date,
            'referenceType' => $this->reference_type,
            'description' => $this->description,
            'recordedByEmployeeId' => $this->recorded_by_employee_id,
            'accountId' => $this->account_id,
            'recordedByEmployee' => $name,
            'account' => new AccountResource($this->account)->account_name
        ];
    }
}
