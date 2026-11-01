<?php

namespace Tests\Unit;

use App\Models\Work;
use PHPUnit\Framework\TestCase;

class WorkStatusLabelTest extends TestCase
{
    public function test_legacy_published_annals_status_is_shown_as_presented(): void
    {
        $work = new Work(['status' => Work::STATUS_PUBLISHED_ANNALS]);

        $this->assertSame('Apresentado', $work->statusLabel());
        $this->assertNotContains(Work::STATUS_PUBLISHED_ANNALS, Work::statusOptions());
        $this->assertArrayNotHasKey(Work::STATUS_PUBLISHED_ANNALS, Work::statusLabels());
    }
}
