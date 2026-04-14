<?php

namespace Database\Seeders;

use App\Models\ClinicMaterial;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = Supplier::all();
        $materials = ClinicMaterial::all();

        if ($suppliers->isEmpty() || $materials->isEmpty()) {
            return;
        }

        $orders = [
            [
                'supplier_name' => $suppliers->first()->organization_name,
                'date' => now()->subDays(10),
                'status' => 'completed',
                'notes' => 'Initial supply order',
            ],
            [
                'supplier_name' => $suppliers->count() > 1 ? $suppliers[1]->organization_name : $suppliers->first()->organization_name,
                'date' => now()->subDays(5),
                'status' => 'pending',
                'notes' => 'Restocking order',
            ],
        ];

        foreach ($orders as $orderData) {
            $order = Order::create($orderData);

            $materials->random(min(3, $materials->count()))->each(function ($material) use ($order) {
                $quantity = rand(5, 50);
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $material->id,
                    'quantity' => $quantity,
                    'unit_price' => $material->amount,
                    'total_price' => $quantity * $material->amount,
                ]);
            });
        }
    }
}
