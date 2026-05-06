<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'expenseCategory' => $this->expense_category,
            'unit' => $this->unit,
            'amount' => $this->amount,
            'expenseDate' => $this->expense_date,
            'description' => $this->description,
            'paidByEmployeeId' => $this->paidByEmployee_id,
            'paidByEmployeeName' => $this->Employee->f_name . ' ' . $this->Employee->l_name,
            'accountId' => $this->account_id,
        ];
    }
}
