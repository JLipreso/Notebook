<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\CityMunicipality;
use App\Models\Province;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * PSGC address chain + profile (2026-09-13-005 Phase 005).
 *
 * Builds its own miniature PSGC tree rather than seeding 42k real rows: these
 * assert the ENDPOINT behaviour, and the real data is verified by the seeder's
 * own counts. The tree deliberately includes both branches — a province-bearing
 * city and an independent city.
 */
class AddressProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        Region::create(['code' => '0700000000', 'name' => 'Region VII (Central Visayas)']);
        Region::create(['code' => '1300000000', 'name' => 'National Capital Region (NCR)']);

        Province::create(['code' => '0702200000', 'region_code' => '0700000000', 'name' => 'Cebu']);

        // Under a province.
        CityMunicipality::create([
            'code' => '0702201000', 'province_code' => '0702200000',
            'region_code' => '0700000000', 'name' => 'Alcantara', 'class' => 'municipality',
        ]);
        // Highly urbanized: independent of any province, but NOT in NCR.
        CityMunicipality::create([
            'code' => '0730600000', 'province_code' => null,
            'region_code' => '0700000000', 'name' => 'City of Cebu', 'class' => 'city',
        ]);
        // NCR city: also province-less.
        CityMunicipality::create([
            'code' => '1380100000', 'province_code' => null,
            'region_code' => '1300000000', 'name' => 'City of Manila', 'class' => 'city',
        ]);

        Barangay::create(['code' => '0702201001', 'city_muni_code' => '0702201000', 'name' => 'Cabadiangan']);
        Barangay::create(['code' => '0730600001', 'city_muni_code' => '0730600000', 'name' => 'Apas']);
        Barangay::create(['code' => '1380100001', 'city_muni_code' => '1380100000', 'name' => 'Barangay 1']);
    }

    public function test_regions_are_public_and_sorted(): void
    {
        $this->getJson('/api/address/regions')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            // Sorted by name: NCR before Region VII.
            ->assertJsonPath('data.0.name', 'National Capital Region (NCR)');
    }

    public function test_provinces_are_filtered_by_region(): void
    {
        $this->getJson('/api/address/regions/0700000000/provinces')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cebu');

        // NCR has no provinces at all.
        $this->getJson('/api/address/regions/1300000000/provinces')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_cities_are_filtered_by_province(): void
    {
        $this->getJson('/api/address/provinces/0702200000/cities')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Alcantara');
    }

    /**
     * The independent-city branch. NOT an NCR special case: highly urbanized
     * cities sit under a region with no province, in every region.
     */
    public function test_region_cities_returns_only_province_less_cities(): void
    {
        $this->getJson('/api/address/regions/0700000000/cities')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'City of Cebu')
            ->assertJsonPath('data.0.province_code', null);

        $this->getJson('/api/address/regions/1300000000/cities')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'City of Manila');
    }

    public function test_barangays_are_filtered_by_city(): void
    {
        $this->getJson('/api/address/cities/0730600000/barangays')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Apas');
    }

    public function test_chain_resolves_a_saved_barangay_both_ways(): void
    {
        // Through a province.
        $this->getJson('/api/address/barangays/0702201001/chain')
            ->assertOk()
            ->assertJsonPath('data.region.name', 'Region VII (Central Visayas)')
            ->assertJsonPath('data.province.name', 'Cebu')
            ->assertJsonPath('data.city_municipality.name', 'Alcantara')
            ->assertJsonPath('data.barangay.name', 'Cabadiangan');

        // Independent city: province is null, region still resolves.
        $this->getJson('/api/address/barangays/0730600001/chain')
            ->assertOk()
            ->assertJsonPath('data.province', null)
            ->assertJsonPath('data.city_municipality.name', 'City of Cebu')
            ->assertJsonPath('data.region.name', 'Region VII (Central Visayas)');
    }

    public function test_unknown_barangay_chain_is_a_404_envelope(): void
    {
        $this->getJson('/api/address/barangays/9999999999/chain')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_non_numeric_codes_do_not_match_the_route(): void
    {
        // ->where('code', '[0-9]{10}') keeps junk out of the query entirely.
        $this->getJson('/api/address/cities/not-a-code/barangays')->assertNotFound();
        $this->getJson('/api/address/cities/123/barangays')->assertNotFound();
    }

    public function test_profile_requires_auth(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized();
        $this->putJson('/api/profile', ['first_name' => 'X'])->assertUnauthorized();
    }

    public function test_profile_updates_and_rehydrates(): void
    {
        $user = User::factory()->create(['barangay_code' => null]);

        $this->actingAs($user)->putJson('/api/profile', [
            'first_name' => 'Maria',
            'barangay_code' => '0730600001',
            'address_line' => '12 Mabini Street',
        ])->assertOk()->assertJsonPath('data.first_name', 'Maria');

        $this->actingAs($user)->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.barangay_code', '0730600001')
            ->assertJsonPath('data.address_line', '12 Mabini Street');
    }

    public function test_unknown_barangay_code_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/profile', ['barangay_code' => '9999999999'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['barangay_code']);
    }

    public function test_email_and_role_are_not_editable_from_the_profile(): void
    {
        $user = User::factory()->create(['email' => 'real@example.com', 'role' => 'student']);

        $this->actingAs($user)->putJson('/api/profile', [
            'email' => 'hacker@example.com',
            'role' => 'admin',
            'first_name' => 'Maria',
        ])->assertOk();

        $user->refresh();

        $this->assertSame('real@example.com', $user->email);
        $this->assertSame('student', $user->role);
        $this->assertSame('Maria', $user->first_name);
    }

    public function test_address_can_be_cleared(): void
    {
        $user = User::factory()->create(['barangay_code' => '0730600001']);

        $this->actingAs($user)->putJson('/api/profile', ['barangay_code' => null])->assertOk();

        $this->assertNull($user->fresh()->barangay_code);
    }
}
