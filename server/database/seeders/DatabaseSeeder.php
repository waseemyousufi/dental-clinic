<?php

namespace Database\Seeders;

use App\Models\ProcedureInventory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ClinicOwnerSeeder::class);
        $this->call(ProcedureSeeder::class);
        $this->call(PositionSeeder::class);
        $this->call(BranchSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(EmployeeSeeder::class);
        $this->call(AccountSeeder::class);
        // $this->call(TransactionSeeder::class);
        // $this->call(PatientSeeder::class);
        // $this->call(ClinicMaterialSeeder::class);
        // $this->call(ClinicAssetSeeder::class);
        // $this->call(ItemSeeder::class);
        // $this->call(EmployeeSalarySeeder::class);
        // $this->call(EmployeeExperienceSeeder::class);
        $this->call(PrescriptionSeeder::class);
        // $this->call(ReceptionSeeder::class);
        // $this->call(ClinicExpenseSeeder::class);
        // $this->call(DentalXraySeeder::class);
        // $this->call(AppointmentSeeder::class);
        // $this->call(TreatmentPlanSeeder::class);
        // $this->call(TreatmentSeeder::class);
        // $this->call(AllergySeeder::class);
        // $this->call(PatientFileSeeder::class);
        // $this->call(ManyToManySeeder::class);
        // $this->call(SupplierSeeder::class);
        // $this->call(OrderSeeder::class);
        // $this->call(ShelfSeeder::class);
        // $this->call(InventoryStockSeeder::class);
        $this->call(TeethReferenceSeeder::class);
        $this->call(ConditionLibrarySeeder::class);
        // $this->call(ProcedureInventorySeeder::class);
    }
}
