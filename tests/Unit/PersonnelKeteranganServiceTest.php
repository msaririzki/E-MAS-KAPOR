<?php

namespace Tests\Unit;

use App\Services\PersonnelKeteranganService;
use PHPUnit\Framework\TestCase;

class PersonnelKeteranganServiceTest extends TestCase
{
    public function test_it_keeps_valid_human_readable_keterangan(): void
    {
        $service = new PersonnelKeteranganService;

        $this->assertSame('STAF', $service->normalizeValue(' STAF '));
        $this->assertSame('RESINTELPAM', $service->normalizeValue('RESINTELPAM'));
    }

    public function test_it_discards_size_like_or_numeric_noise(): void
    {
        $service = new PersonnelKeteranganService;

        $this->assertNull($service->normalizeValue('4|14,5|15|15,5|16|16,5|17|17,5|18|18,5|19|19,5|20|21|22'));
        $this->assertNull($service->normalizeValue('27|28|29|30|31|32|33|'));
        $this->assertNull($service->normalizeValue('1'));
        $this->assertNull($service->normalizeValue('14,5/15/16'));
    }
}
