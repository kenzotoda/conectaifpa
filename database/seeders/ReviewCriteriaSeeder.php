<?php

namespace Database\Seeders;

use App\Models\ReviewCriterion;
use Illuminate\Database\Seeder;

class ReviewCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            ['name' => 'originalidade', 'description' => 'Avalia inovação e contribuição inédita.'],
            ['name' => 'metodologia', 'description' => 'Avalia rigor metodológico do trabalho.'],
            ['name' => 'clareza', 'description' => 'Avalia organização textual e compreensão.'],
            ['name' => 'relevancia', 'description' => 'Avalia impacto acadêmico/social do tema.'],
        ];

        foreach ($criteria as $criterion) {
            ReviewCriterion::updateOrCreate(
                ['name' => $criterion['name']],
                [
                    'description' => $criterion['description'],
                    'default_weight' => 1.00,
                    'is_active' => true,
                ]
            );
        }
    }
}
