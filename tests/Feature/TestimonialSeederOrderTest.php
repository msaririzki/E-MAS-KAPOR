<?php

namespace Tests\Feature;

use App\Models\ItemReview;
use App\Models\PersonnelItemAllocation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ItemReviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialSeederOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_item_reviews_after_demo_personnel_exist(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, User::role('personil')->count());
        $this->assertGreaterThan(0, PersonnelItemAllocation::count());
        $this->assertGreaterThan(0, ItemReview::count());

        $seededUsers = User::query()
            ->where('nrp_nip', 'like', ItemReviewSeeder::IDENTIFIER_PREFIX.'%')
            ->pluck('id');

        $this->assertCount(ItemReviewSeeder::SEEDED_PERSONNEL_COUNT, $seededUsers);
        $this->assertSame(
            ItemReviewSeeder::SEEDED_PERSONNEL_COUNT,
            ItemReview::query()->whereIn('user_id', $seededUsers)->count(),
        );
    }
}
