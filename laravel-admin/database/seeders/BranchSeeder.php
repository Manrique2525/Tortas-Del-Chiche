<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $schedule = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            $schedule[$day] = ['open' => '07:00', 'close' => '14:00'];
        }

        Branch::create([
            'key' => 'atasta',
            'name' => 'Sucursal Atasta',
            'address' => 'Av. 27 de Febrero #2616, Colonia Atasta',
            'phone' => '993 309 2124',
            'whatsapp' => '529933092124',
            'schedule' => $schedule,
            'schedule_text' => 'Lunes a Domingo de 7:00 am a 2:00 pm',
            'latitude' => 17.986549496538164,
            'longitude' => -92.95316056151032,
            'didi_url' => 'https://www.didi-food.com/es-MX/food/store/5764607750370494503/LAS-TORTAS-DEL-CHICHE',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Branch::create([
            'key' => 'av_universidad',
            'name' => 'Sucursal AV Universidad',
            'address' => 'Av Universidad 392, Colonia Casa Blanca',
            'phone' => '993 220 6325',
            'whatsapp' => '529932206325',
            'schedule' => $schedule,
            'schedule_text' => 'Lunes a Domingo de 7:00 am a 2:00 pm',
            'latitude' => 18.0128788979183,
            'longitude' => -92.91857173267503,
            'didi_url' => 'https://www.didi-food.com/es-MX/food/store/5764610375841221517',
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }
}
