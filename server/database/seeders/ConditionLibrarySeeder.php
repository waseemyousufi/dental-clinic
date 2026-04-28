<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConditionLibrarySeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('condition_library')->truncate();

        $conditions = [
            // Your Original Restoration & Finding Icons
            [
                'label' => 'Dental Bridge',
                'slug' => 'dental_bridge',
                'category' => 'restoration',
                'ui_color' => '#F59E0B',
                // 'render_type' => 'bridge',
                'svg_path' => 'M5 50 Q 50 20, 95 50',
            ],
            [
                'label' => 'Diastema',
                'slug' => 'diastema',
                'category' => 'finding',
                'ui_color' => '#3B82F6',
                // 'render_type' => 'icon',
                'svg_path' => 'M30 20 L30 80 M70 20 L70 80 M30 50 L10 50 M20 40 L10 50 L20 60 M70 50 L90 50 M80 40 L90 50 L80 60',
            ],
            [
                'label' => 'Extraction Required',
                'slug' => 'extraction',
                'category' => 'procedure',
                'ui_color' => '#EF4444',
                // 'render_type' => 'icon',
                'svg_path' => 'M20 20 L80 80 M80 20 L20 80',
            ],
            [
                'label' => 'Fracture',
                'slug' => 'fracture',
                'category' => 'finding',
                'ui_color' => '#EF4444',
                // 'render_type' => 'icon',
                'svg_path' => 'M20 50 L40 30 L60 70 L80 50',
            ],
            [
                'label' => 'Full Crown',
                'slug' => 'full_crown',
                'category' => 'restoration',
                'ui_color' => '#3B82F6',
                // 'render_type' => 'icon',
                'svg_path' => 'M15 15 H85 V85 H15 Z M15 40 H85 M15 65 H85',
            ],
            [
                'label' => 'Gingival Recession',
                'slug' => 'gingival_recession',
                'category' => 'finding',
                'ui_color' => '#F472B6',
                // 'render_type' => 'gum_line',
                'svg_path' => 'M10 50 Q 30 30, 50 50 T 90 50',
            ],
            [
                'label' => 'Impacted Tooth',
                'slug' => 'impacted_tooth',
                'category' => 'finding',
                'ui_color' => '#8B5CF6',
                // 'render_type' => 'icon',
                'svg_path' => 'M50 50 m-40,0 a40,40 0 1,0 80,0 a40,40 0 1,0 -80,0 M50 10 V30 M40 20 L50 10 L60 20',
            ],
            [
                'label' => 'Implant',
                'slug' => 'implant',
                'category' => 'restoration',
                'ui_color' => '#6366F1',
                // 'render_type' => 'icon',
                'svg_path' => 'M30 20 H70 M35 35 H65 M40 50 H60 M45 65 H55 M50 20 V80 M45 85 H55',
            ],
            [
                'label' => 'Loose Tooth (Mobility)',
                'slug' => 'loose_tooth',
                'category' => 'finding',
                'ui_color' => '#F59E0B',
                // 'render_type' => 'icon',
                'svg_path' => 'M20 50 H80 M30 35 L20 50 L30 65 M70 35 L80 50 L70 65',
            ],
            [
                'label' => 'Missing Tooth',
                'slug' => 'missing',
                'category' => 'finding',
                'ui_color' => '#9CA3AF',
                // 'render_type' => 'icon',
                'svg_path' => 'M20 30 L50 80 L80 30',
            ],
            [
                'label' => 'Orthodontic Brackets',
                'slug' => 'orthodontic_brackets',
                'category' => 'procedure',
                'ui_color' => '#10B981',
                // 'render_type' => 'icon',
                'svg_path' => 'M30 30 H70 V70 H30 Z M0 50 H100 M50 30 V70',
            ],
            [
                'label' => 'Root Canal (RCT)',
                'slug' => 'root_canal',
                'category' => 'procedure',
                'ui_color' => '#FFD700',
                // 'render_type' => 'root_internal',
                'svg_path' => 'M50 10 V90',
            ],
            [
                'label' => 'Caries',
                'slug' => 'caries',
                'category' => 'finding',
                'ui_color' => '#EF4444',
                // 'render_type' => 'surface',
                'svg_path' => null,
            ],
            [
                'label' => 'Recurrent Caries',
                'slug' => 'recurrent_caries',
                'category' => 'finding',
                'ui_color' => '#EF4444',
                // 'render_type' => 'surface',
                'svg_path' => null,
            ],
            [
                'label' => 'Periapical Abscess',
                'slug' => 'abscess',
                'category' => 'finding',
                'ui_color' => '#FF4500',
                // 'render_type' => 'root_apex',
                // 'svg_path' => 'M50 85 m-5,0 a5,5 0 1,0 10,0 a5,5 0 1,0 -10,0',
                'svg_path' => null,
            ],
            [
                'label' => 'Apicoectomy',
                'slug' => 'apicoectomy',
                'category' => 'procedure',
                'ui_color' => '#9370DB',
                // 'render_type' => 'root_apex',
                // 'svg_path' => 'M50 85 m-5,0 a5,5 0 1,0 10,0 a5,5 0 1,0 -10,0',
                'svg_path' => null,
            ],
            [
                'label' => 'Post & Core',
                'slug' => 'post_core',
                'category' => 'restoration',
                'ui_color' => '#808080',
                // 'render_type' => 'root_internal',
                // 'svg_path' => 'M50 10 V90 M45 85 H55',
                'svg_path' => null,
            ],
            [
                'label' => 'Unerupted',
                'slug' => 'unerupted',
                'category' => 'finding',
                'ui_color' => '#8B5CF6',
                // 'render_type' => 'icon',
                // 'svg_path' =>  'M50 50 m-40,0 a40,40 0 1,0 80,0 a40,40 0 1,0 -80,0 M50 10 V30 M40 20 L50 10 L60 20',
                'svg_path' => null,
            ],
            [
                'label' => 'Sealant',
                'slug' => 'sealant',
                'category' => 'procedure',
                'ui_color' => '#10B981',
                // 'render_type' => 'surface',
                'svg_path' => null,
            ],
            [
                'label' => 'Amalgam Filling',
                'slug' => 'amalgam_filling',
                'category' => 'restoration',
                'ui_color' => '#4B5563',
                // 'render_type' => 'surface',
                'svg_path' => null,
            ],
        ];

        DB::table('condition_library')->insert($conditions);
        Schema::enableForeignKeyConstraints();
    }
}
