<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConditionLibrarySeeder extends Seeder
{
    public function run()
    {
        $conditions = [
            [
                'label' => 'Caries (Cavity)',
                'slug' => 'caries',
                'category' => 'finding',
                'ui_color' => '#EF4444', // Red
            ],
            [
                'label' => 'Existing Filling',
                'slug' => 'filling',
                'category' => 'restoration',
                'ui_color' => '#3B82F6', // Blue
            ],
            [
                'label' => 'Missing Tooth',
                'slug' => 'missing',
                'category' => 'finding',
                'ui_color' => '#9CA3AF', // Gray
            ],
            [
                'label' => 'Bridge / Crown',
                'slug' => 'crown',
                'category' => 'restoration',
                'ui_color' => '#F59E0B', // Amber
            ],
            [
                'label' => 'Root Canal (RCT)',
                'slug' => 'rct',
                'category' => 'procedure',
                'ui_color' => '#10B981', // Green
            ],
            [
                'label' => 'Implant Needed',
                'slug' => 'implant_plan',
                'category' => 'finding',
                'ui_color' => '#8B5CF6', // Purple
            ],
        ];

        DB::table('condition_library')->insert($conditions);
    }
}
