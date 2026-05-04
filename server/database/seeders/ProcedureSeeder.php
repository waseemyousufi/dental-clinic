<?php

namespace Database\Seeders;

use App\Models\Procedure;
use Illuminate\Database\Seeder;

class ProcedureSeeder extends Seeder
{
    public function run(): void
    {
        $procedures = [
            [
                'name' => 'Composite Filling (1 Surface)',
                'slug' => 'composite-filling-1',
                'category' => 'Restorative',
                'base_price' => 1500, // AFN
                'dentist_commission' => 20.00,
                'min_price' => 100,
                'assistant_commission' => 50, // Fixed AFN for Dastyar
            ],
            [
                'name' => 'Simple Extraction',
                'slug' => 'simple-extraction',
                'category' => 'Surgery',
                'base_price' => 1000,
                'dentist_commission' => 15.00,
                'min_price' => 100,
                'assistant_commission' => 30,
            ],
            [
                'name' => 'Scaling & Polishing',
                'slug' => 'scaling-polishing',
                'category' => 'Preventive',
                'base_price' => 800,
                'dentist_commission' => 25.00,
                'min_price' => 100,
                'assistant_commission' => 20,
            ]
        ];

        foreach ($procedures as $p) {
            Procedure::create($p);
        }
    }
}
