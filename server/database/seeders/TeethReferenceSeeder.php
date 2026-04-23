<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeethReferenceSeeder extends Seeder
{
    public function run()
    {
        $teeth = [];

        // --- PERMANENT TEETH (32) ---
        // Quadrants 1-4
        $permanentQuads = [
            1 => 'Upper Right', 2 => 'Upper Left',
            3 => 'Lower Left',  4 => 'Lower Right'
        ];

        foreach ($permanentQuads as $quad => $desc) {
            for ($i = 1; $i <= 8; $i++) {
                $teeth[] = [
                    'fdi_code' => "{$quad}{$i}",
                    'universal_code' => $this->getUniversal($quad, $i),
                    'type' => 'permanent',
                    'quadrant' => $quad,
                    'default_position' => ($quad * 10) + $i,
                    'created_at' => now(),
                ];
            }
        }

        // --- PRIMARY TEETH (20) ---
        // Quadrants 5-8
        $primaryQuads = [
            5 => 'Upper Right', 6 => 'Upper Left',
            7 => 'Lower Left',  8 => 'Lower Right'
        ];

        foreach ($primaryQuads as $quad => $desc) {
            for ($i = 1; $i <= 5; $i++) {
                $teeth[] = [
                    'fdi_code' => "{$quad}{$i}",
                    'universal_code' => $this->getUniversalPrimary($quad, $i),
                    'type' => 'primary',
                    'quadrant' => $quad,
                    'default_position' => ($quad * 10) + $i,
                    'created_at' => now(),
                ];
            }
        }

        DB::table('teeth_reference')->insert($teeth);
    }

    private function getUniversal($quad, $pos) {
        // Simple logic to map FDI to Universal 1-32 if needed
        return "";
    }

    private function getUniversalPrimary($quad, $pos) {
        // Simple logic to map FDI to Universal A-T if needed
        return "";
    }
}
