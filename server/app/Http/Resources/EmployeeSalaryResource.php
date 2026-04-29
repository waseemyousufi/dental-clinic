<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeSalaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'salaryMonth' => $this->salary_month,
            'amount' => $this->amount,
            'accountName' => $this->AccountTransaction->Account->account_name,
            'bonus' => $this->bonus,
            'totalAmount' => $this->total_amount,
            'remark' => $this->remark,
            'paidByTransaction_id' => $this->paidByAccountTransaction_id,
            'employeeId' => $this->employee_id,
            'employee' => $this->Employee->f_name . ' ' . $this->employee->l_name
        ];
    }
}
