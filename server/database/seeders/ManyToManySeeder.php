<?php

namespace Database\Seeders;

use App\Models\AccountTransaction;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\Position;
use App\Models\Treatment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ManyToManySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branch1 = Branch::where('clinic_owner_id', 1)->first();
        $position = Position::find(1);
        $appointment = Appointment::where('branch_id', $branch1?->id)->first();
        $employee1 = Employee::where('branch_id', $branch1?->id)->first();
        $treatment = Treatment::where('branch_id', $branch1?->id)->first();
        $patient1 = Patient::where('branch_id', $branch1?->id)->first();
        $transaction = AccountTransaction::where('branch_id', $branch1?->id)->first();

        $branch1->positions()->save($position);
        $patient1->appointments()->save($appointment);
        $employee1->treatments()->save($treatment);
        $branch1->accountTransactions()->save($transaction);
        $employee1->appointments()->save($appointment);
    }
}
