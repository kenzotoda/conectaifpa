<?php

namespace App\Services;

use App\Models\Work;

class WorkScoreService
{
    /**
     * Calcula média simples de todas as avaliações finalizadas do trabalho.
     */
    public function calculateFinalScore(Work $work): ?float
    {
        $reviews = $work->reviews()
            ->whereNotNull('submitted_at')
            ->whereNotNull('score')
            ->get();

        if ($reviews->isEmpty()) {
            return null;
        }

        $scoresSum = (float) $reviews->sum(fn ($review) => (float) $review->score);
        $scoresCount = $reviews->count();

        return round($scoresSum / $scoresCount, 2);
    }
}
