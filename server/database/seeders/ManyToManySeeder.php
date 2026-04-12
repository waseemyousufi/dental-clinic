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
        $branch1 = Branch::find(1);
        $position = Position::find(1);
        $appointment = Appointment::find(1);
        $employee1 = Employee::find(1);
        $treatment = Treatment::find(1);
        $patient1 = Patient::find(1);
        $transaction = AccountTransaction::find(1);

        $branch1->positions()->save($position);
        $patient1->appointments()->save($appointment);
        $employee1->treatments()->save($treatment);
        $branch1->accountTransactions()->save($transaction);
        $employee1->appointments()->save($appointment);
    }
}
