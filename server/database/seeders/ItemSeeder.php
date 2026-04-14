<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ProductPrice;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // ================= PROSTHETICS =================
            [
                'name' => 'Dental Crown',
                'category' => 'prosthetics',
                'materials' => ['Porcelain'],
                'description' => 'Tooth cap',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Dental Bridge',
                'category' => 'prosthetics',
                'materials' => ['Ceramic'],
                'description' => 'Replace missing teeth',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Implant Fixture',
                'category' => 'prosthetics',
                'materials' => ['Titanium'],
                'description' => 'Implant base',
                'track_stock' => true,
                'requires_batch' => true,
                'requires_expiry' => true,
                'is_consumable' => true,
            ],
            [
                'name' => 'Denture',
                'category' => 'prosthetics',
                'materials' => ['Acrylic'],
                'description' => 'Removable teeth',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Veneer',
                'category' => 'prosthetics',
                'materials' => ['Porcelain', 'Composite'],
                'description' => 'Cosmetic tooth covering',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],

            // ================= DEVICES =================
            [
                'name' => 'Dental Chair',
                'category' => 'devices',
                'materials' => ['Metal', 'Leather'],
                'description' => 'Patient chair',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Compressor',
                'category' => 'devices',
                'materials' => ['Metal'],
                'description' => 'Air supply',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Autoclave',
                'category' => 'devices',
                'materials' => ['Steel'],
                'description' => 'Sterilizer',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Ultrasonic Cleaner',
                'category' => 'devices',
                'materials' => ['Metal'],
                'description' => 'Instrument cleaning',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'X-Ray Unit',
                'category' => 'devices',
                'materials' => ['Metal', 'Plastic'],
                'description' => 'Digital x-ray machine',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],

            // ================= FURNITURE =================
            [
                'name' => 'Dental Cabinet',
                'category' => 'furniture',
                'materials' => ['Wood'],
                'description' => 'Storage',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Dentist Stool',
                'category' => 'furniture',
                'materials' => ['Metal', 'Foam'],
                'description' => 'Doctor seating',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Patient Waiting Chair',
                'category' => 'furniture',
                'materials' => ['Metal', 'Fabric'],
                'description' => 'Reception seating',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Reception Desk',
                'category' => 'furniture',
                'materials' => ['Wood', 'Glass'],
                'description' => 'Front desk',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Instrument Tray',
                'category' => 'furniture',
                'materials' => ['Steel'],
                'description' => 'Sterile tray for tools',
                'track_stock' => false,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],

            // ================= INSTRUMENTS =================
            [
                'name' => 'Mouth Mirror',
                'category' => 'instruments',
                'materials' => ['Steel'],
                'description' => 'Oral examination',
                'track_stock' => true,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Explorer Probe',
                'category' => 'instruments',
                'materials' => ['Steel'],
                'description' => 'Detect decay',
                'track_stock' => true,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Cotton Pliers',
                'category' => 'instruments',
                'materials' => ['Steel'],
                'description' => 'Handle materials',
                'track_stock' => true,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Scaler',
                'category' => 'instruments',
                'materials' => ['Steel'],
                'description' => 'Remove calculus',
                'track_stock' => true,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],
            [
                'name' => 'Curette',
                'category' => 'instruments',
                'materials' => ['Steel'],
                'description' => 'Periodontal cleaning',
                'track_stock' => true,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => false,
            ],

            // ================= MEDICATIONS =================
            [
                'name' => 'Lidocaine Cartridge',
                'category' => 'medications',
                'materials' => ['Liquid'],
                'description' => 'Local anesthetic',
                'track_stock' => true,
                'requires_batch' => true,
                'requires_expiry' => true,
                'is_consumable' => true,
            ],
            [
                'name' => 'Ibuprofen Tablets',
                'category' => 'medications',
                'materials' => ['Tablet'],
                'description' => 'Pain relief',
                'track_stock' => true,
                'requires_batch' => true,
                'requires_expiry' => true,
                'is_consumable' => true,
            ],
            [
                'name' => 'Amoxicillin Capsules',
                'category' => 'medications',
                'materials' => ['Capsule'],
                'description' => 'Antibiotic',
                'track_stock' => true,
                'requires_batch' => true,
                'requires_expiry' => true,
                'is_consumable' => true,
            ],
            [
                'name' => 'Chlorhexidine Mouthwash',
                'category' => 'medications',
                'materials' => ['Liquid'],
                'description' => 'Antiseptic rinse',
                'track_stock' => true,
                'requires_batch' => true,
                'requires_expiry' => true,
                'is_consumable' => true,
            ],
            [
                'name' => 'Fluoride Gel',
                'category' => 'medications',
                'materials' => ['Gel'],
                'description' => 'Tooth strengthening',
                'track_stock' => true,
                'requires_batch' => true,
                'requires_expiry' => true,
                'is_consumable' => true,
            ],

            // ================= CONSUMABLES =================
            [
                'name' => 'Gloves',
                'category' => 'consumables',
                'materials' => ['Latex'],
                'description' => 'Hand protection',
                'track_stock' => true,
                'requires_batch' => false,
                'requires_expiry' => true,
                'is_consumable' => true,
            ],
            [
                'name' => 'Face Mask',
                'category' => 'consumables',
                'materials' => ['Fabric'],
                'description' => 'Air protection',
                'track_stock' => true,
                'requires_batch' => false,
                'requires_expiry' => true,
                'is_consumable' => true,
            ],
            [
                'name' => 'Cotton Rolls',
                'category' => 'consumables',
                'materials' => ['Cotton'],
                'description' => 'Moisture control',
                'track_stock' => true,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => true,
            ],
            [
                'name' => 'Gauze Pads',
                'category' => 'consumables',
                'materials' => ['Cotton'],
                'description' => 'Wound care',
                'track_stock' => true,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => true,
            ],
            [
                'name' => 'Suction Tips',
                'category' => 'consumables',
                'materials' => ['Plastic'],
                'description' => 'Fluid suction',
                'track_stock' => true,
                'requires_batch' => false,
                'requires_expiry' => false,
                'is_consumable' => true,
            ],
        ];

        foreach ($items as $itemData) {
            $item = Item::create($itemData);

            // Create a base price for each item
            $priceMap = [
                'prosthetics' => [150, 500],
                'devices' => [500, 15000],
                'furniture' => [200, 3000],
                'instruments' => [20, 150],
                'medications' => [5, 50],
                'consumables' => [2, 30],
            ];

            $range = $priceMap[$item->category] ?? [10, 100];
            $price = rand($range[0], $range[1]) + 0.99;

            ProductPrice::create([
                'pricable_id' => $item->id,
                'pricable_type' => Item::class,
                'price' => $price,
                'effective_from' => now(),
                'is_active' => true,
            ]);
        }
    }
}
