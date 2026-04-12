<?php

namespace Database\Seeders;

use App\Models\AccountTransaction;
use App\Models\Branch;
use App\Models\ClinicMaterial;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;



class ClinicMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cm = new ClinicMaterial();

        $outlay1 = $cm->create([
            'materials_name' => 'mask',
            'quantity' => 5,
            'amount' => 400,
            'total_amount' => 2000,
            'expense_date' => '2024/04/04',
            'description' => 'for daily use',
        ]);
        
        $outlay2 = $cm->create([
            'materials_name' => 'clinic gloves',
            'quantity' => 5,
            'amount' => 500,
            'total_amount' => 2500,
            'expense_date' => '2024/04/04',
            'description' => 'for daily use',
        ]);
  

        $transaction = AccountTransaction::find(3);
        $outlay1->accountTransactions()->sync($transaction);
        $outlay2->accountTransactions()->sync($transaction);

        $branch = Branch::find(1);
        $outlay1->branches()->sync($branch);
        $outlay2->branches()->sync($branch);
    }
}
