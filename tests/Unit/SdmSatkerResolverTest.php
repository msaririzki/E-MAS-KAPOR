<?php

namespace Tests\Unit;

use App\Models\Satker;
use App\Services\SdmSatkerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SdmSatkerResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prefers_specific_polda_unit_over_generic_polda(): void
    {
        Satker::create([
            'name' => 'POLDA NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $satbrimob = Satker::create([
            'name' => 'SATBRIMOB',
            'code' => 'SATBRIMOB',
            'sort_order' => 2,
        ]);

        $resolver = new SdmSatkerResolver;
        $result = $resolver->resolve('BANIT II SUBDEN IV DENGEGANA SATBRIMOB POLDA NTB');

        $this->assertSame($satbrimob->id, $result['satker_id']);
        $this->assertSame('SATBRIMOB', $result['satker_name']);
    }

    public function test_it_maps_polsek_positions_to_parent_polres(): void
    {
        Satker::create([
            'name' => 'POLDA NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $polres = Satker::create([
            'name' => 'POLRES LOMBOK BARAT',
            'code' => 'RES-LOMBOK-BARAT',
            'sort_order' => 2,
        ]);

        $resolver = new SdmSatkerResolver;
        $result = $resolver->resolve('BANIT UNIT RESKRIM POLSEK LABUAPI POLRES LOMBOK BARAT POLDA NTB');

        $this->assertSame($polres->id, $result['satker_id']);
        $this->assertSame('POLRES LOMBOK BARAT', $result['satker_name']);
        $this->assertSame('polres', $result['recipient_scope']);
    }

    public function test_it_can_map_karorena_title_to_biro_rena(): void
    {
        Satker::create([
            'name' => 'POLDA NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $biroRena = Satker::create([
            'name' => 'BIRO RENA',
            'code' => 'BIRO-RENA',
            'sort_order' => 2,
        ]);

        $resolver = new SdmSatkerResolver;
        $result = $resolver->resolve('KARORENA POLDA NTB');

        $this->assertSame($biroRena->id, $result['satker_id']);
        $this->assertSame('BIRO RENA', $result['satker_name']);
    }

    public function test_it_can_map_karo_sdm_title_to_biro_sdm(): void
    {
        Satker::create([
            'name' => 'POLDA NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $biroSdm = Satker::create([
            'name' => 'BIRO SDM',
            'code' => 'BIRO-SDM',
            'sort_order' => 2,
        ]);

        $resolver = new SdmSatkerResolver;
        $result = $resolver->resolve('KARO SDM POLDA NTB');

        $this->assertSame($biroSdm->id, $result['satker_id']);
        $this->assertSame('BIRO SDM', $result['satker_name']);
    }

    public function test_it_can_map_ppa_dan_ppo_title_to_new_directorate(): void
    {
        Satker::create([
            'name' => 'POLDA NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $ditresPpa = Satker::create([
            'name' => 'DIT RES PPA DAN PPO',
            'code' => 'RESPPAPPO',
            'sort_order' => 2,
        ]);

        $resolver = new SdmSatkerResolver;
        $result = $resolver->resolve('PS. KANIT SUBDIT PPA DAN PPO DIT RES PPA DAN PPO POLDA NTB');

        $this->assertSame($ditresPpa->id, $result['satker_id']);
        $this->assertSame('DIT RES PPA DAN PPO', $result['satker_name']);
    }

    public function test_it_can_map_rolog_title_to_biro_logistik(): void
    {
        Satker::create([
            'name' => 'POLDA NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $biroLog = Satker::create([
            'name' => 'BIRO LOGISTIK',
            'code' => 'BIRO-LOG',
            'sort_order' => 2,
        ]);

        $resolver = new SdmSatkerResolver;
        $result = $resolver->resolve('BAMIN ROLOG POLDA NTB');

        $this->assertSame($biroLog->id, $result['satker_id']);
        $this->assertSame('BIRO LOGISTIK', $result['satker_name']);
    }
}
