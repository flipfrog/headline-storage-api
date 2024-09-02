<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Headline;

class HeadlineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1 links 2, 3 each other
        // 2 links 4, 5 each other
        Headline::factory()->count(30)->create();
        /** @var Headline[] $headlines */
        $headlines = Headline::query()->orderBy('id')->get();
        $headlines[0]->forwardRefs()->sync([2, 3]);
        $headlines[1]->forwardRefs()->sync([4, 5]);
    }
}
