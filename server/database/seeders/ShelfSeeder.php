<?php

namespace Database\Seeders;

use App\Models\Shelf;
use Illuminate\Database\Seeder;

class ShelfSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shelves = [
            [
                'shelf_name' => 'Glass Cabinet A1',
                'shelf_type' => 'glass',
                'access_pin' => '1234',
                'total_capacity_cm3' => 50000.00,
                'category_restriction' => null,
            ],
            [
                'shelf_name' => 'Metal Cabinet B1',
                'shelf_type' => 'metal',
                'access_pin' => '5678',
                'total_capacity_cm3' => 75000.00,
                'category_restriction' => 'Surgical',
            ],
            [
                'shelf_name' => 'Refrigerator R1',
                'shelf_type' => 'refrigerator',
                'access_pin' => '9012',
                'total_capacity_cm3' => 30000.00,
                'category_restriction' => 'Consumable',
            ],
            [
                'shelf_name' => 'Wood Cabinet C1',
                'shelf_type' => 'wood',
                'access_pin' => '3456',
                'total_capacity_cm3' => 60000.00,
                'category_restriction' => null,
            ],
        ];

        foreach ($shelves as $shelf) {
            Shelf::create($shelf);
        }
    }
}
