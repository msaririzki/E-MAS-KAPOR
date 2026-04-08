<?php

namespace Tests\Feature;

use App\Models\Satker;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperadminStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_superadmin_can_view_testimonial_statistics(): void
    {
        $satker = Satker::create([
            'name' => 'Biro SDM',
            'code' => 'BIRO-SDM',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $personilA = User::factory()->create([
            'name' => 'Briptu Andi',
            'satker_id' => $satker->id,
        ]);
        $personilA->assignRole('personil');

        $personilB = User::factory()->create([
            'name' => 'Aipda Sinta',
            'satker_id' => $satker->id,
        ]);
        $personilB->assignRole('personil');

        Testimonial::create([
            'user_id' => $personilA->id,
            'message' => 'Aplikasi sangat membantu rekap kapor menjadi cepat dan rapi.',
            'rating' => 5,
        ]);

        Testimonial::create([
            'user_id' => $personilB->id,
            'message' => 'Tampilan sudah bagus, namun respons form masih bisa dipercepat.',
            'rating' => 3,
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.statistics'));

        $response->assertOk();
        $response->assertViewHas('totalTestimonials', 2);
        $response->assertViewHas('averageRating', 4.0);
        $response->assertSeeText('Statistik Testimoni');
        $response->assertSeeText('Biro SDM');
        $response->assertSeeText('Aplikasi sangat membantu rekap kapor menjadi cepat dan rapi.');
    }

    public function test_statistics_page_shows_empty_state_when_no_testimonials_exist(): void
    {
        $satker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)->get(route('superadmin.statistics'));

        $response->assertOk();
        $response->assertViewHas('totalTestimonials', 0);
        $response->assertSeeText('Belum ada testimoni masuk');
    }
}
