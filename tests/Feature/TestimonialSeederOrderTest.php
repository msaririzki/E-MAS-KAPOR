<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialSeederOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_testimonials_after_demo_personnel_exist(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, User::role('personil')->count());
        $this->assertGreaterThan(0, Testimonial::count());
    }
}
