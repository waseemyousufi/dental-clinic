<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'contact_person_name' => 'John Smith',
                'organization_name' => 'Medical Supplies Co.',
                'phone' => '+1234567890',
                'email' => 'john@medicalsupplies.com',
                'status' => 'active',
                'business_id' => null,
            ],
            [
                'contact_person_name' => 'Jane Doe',
                'organization_name' => 'Dental Equipment Ltd.',
                'phone' => '+0987654321',
                'email' => 'jane@dentalequipment.com',
                'status' => 'active',
                'business_id' => null,
            ],
            [
                'contact_person_name' => 'Robert Johnson',
                'organization_name' => 'Pharma Distributors Inc.',
                'phone' => '+1122334455',
                'email' => 'robert@pharmadist.com',
                'status' => 'inactive',
                'business_id' => null,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
