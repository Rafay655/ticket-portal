<?php

namespace Tests\Feature;

use App\Mail\UserCreatedNotification;
use App\Models\Employee;
use App\Models\Region;
use App\Models\User;
use App\Services\RegionalPocContacts;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountCredentialEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('p_region_id')->default(0);
        });
        foreach ([1 => 'South', 2 => 'Central A', 3 => 'Central B', 4 => 'Central C', 5 => 'North', 6 => 'North West'] as $id => $name) {
            DB::table('regions')->insert(['id' => $id, 'name' => $name, 'p_region_id' => 0]);
        }
    }

    private function user(array $userRegions = [], ?array $employeeRegions = null): User
    {
        $user = new User(['name' => 'Demo Employee', 'email' => 'demo.employee@example.com', 'region_ids' => $userRegions]);
        $user->setRelation('employee', $employeeRegions === null ? null : new Employee(['region_ids' => $employeeRegions]));
        return $user;
    }

    private function contacts(User $user): array
    {
        return (new RegionalPocContacts(config('account_email.regional_pocs')))->forUser($user);
    }

    public function test_each_region_receives_only_its_own_poc(): void
    {
        foreach ([1 => 'Quratul Ain Adnan', 2 => 'Muzaffar Mehmood', 3 => 'Mubashir Hassan', 4 => 'Mubashir Hassan', 5 => 'Abdullah Babar', 6 => 'Abdullah Babar'] as $id => $name) {
            $contacts = $this->contacts($this->user([$id]));
            $this->assertCount(1, $contacts);
            $this->assertSame($name, $contacts[0]['name']);
        }
    }

    public function test_employee_regions_take_precedence_over_user_regions(): void
    {
        $this->assertSame('Abdullah Babar', $this->contacts($this->user([1], [5]))[0]['name']);
    }

    public function test_shared_contact_is_deduplicated_for_multiple_regions(): void
    {
        $this->assertCount(1, $this->contacts($this->user([3, 4])));
        $this->assertCount(1, $this->contacts($this->user([5, 6])));
        $this->assertCount(2, $this->contacts($this->user([1, 5])));
    }

    public function test_missing_or_unknown_region_does_not_expose_all_contacts(): void
    {
        $this->assertSame([], $this->contacts($this->user()));
        $this->assertSame([], $this->contacts($this->user([999])));
    }

    public function test_subregion_uses_its_parent_contact(): void
    {
        DB::table('regions')->insert(['id' => 7, 'name' => 'Islamabad Office', 'p_region_id' => 5]);
        $this->assertSame('Abdullah Babar', $this->contacts($this->user([7]))[0]['name']);
        $user = $this->user();
        $user->sub_region_id = 7;
        $this->assertSame('Abdullah Babar', $this->contacts($user)[0]['name']);
    }

    public function test_legacy_single_region_and_comma_separated_region_ids_are_supported(): void
    {
        $user = $this->user();
        $user->region_id = 2;
        $this->assertSame('Muzaffar Mehmood', $this->contacts($user)[0]['name']);
        $user = $this->user();
        $user->region_ids = '3,4';
        $this->assertCount(1, $this->contacts($user));
    }

    public function test_region_names_ignore_case_spaces_and_hyphens(): void
    {
        $service = new RegionalPocContacts(config('account_email.regional_pocs'));
        $this->assertSame('Abdullah Babar', $service->forRegionNames([' north-west '])[0]['name']);
        $this->assertSame('Muzaffar Mehmood', $service->forRegionNames(['CENTRAL-A'])[0]['name']);
    }

    public function test_parent_cycles_stop_without_exposing_contacts(): void
    {
        DB::table('regions')->insert([
            ['id' => 8, 'name' => 'Unknown One', 'p_region_id' => 9],
            ['id' => 9, 'name' => 'Unknown Two', 'p_region_id' => 8],
        ]);
        $this->assertSame([], $this->contacts($this->user([8])));
    }

    public function test_north_email_contains_only_regional_contact_and_both_store_links(): void
    {
        $html = (new UserCreatedNotification($this->user([5]), 'DemoOnly!2026'))->render();
        $this->assertStringContainsString('Abdullah Babar', $html);
        $this->assertStringContainsString('abdullah.babar@hrpsp.net', $html);
        $this->assertStringNotContainsString('Quratul Ain Adnan', $html);
        $this->assertStringNotContainsString('Muzaffar Mehmood', $html);
        $this->assertStringNotContainsString('Mubashir Hassan', $html);
        $this->assertStringNotContainsString('>Region</th>', $html);
        $this->assertStringContainsString('https://play.google.com/store/apps/details?id=com.flexi.flexiDesk', $html);
        $this->assertStringContainsString('https://apps.apple.com/us/app/flexi-desk/id6756008101', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('DemoOnly!2026', $html);
    }

    public function test_missing_region_email_uses_hr_fallback(): void
    {
        $html = (new UserCreatedNotification($this->user(), 'DemoOnly!2026'))->render();
        $this->assertStringContainsString('mailto:hr@hrpsp.net', $html);
        $this->assertStringNotContainsString('Abdullah Babar', $html);
        $this->assertStringNotContainsString('Quratul Ain Adnan', $html);
    }

    public function test_head_credential_email_also_has_store_links(): void
    {
        $user = $this->user([5]);
        $user->designation_id = 33;
        $html = (new UserCreatedNotification($user, 'DemoOnly!2026'))->render();
        $this->assertStringContainsString('Abdullah Babar', $html);
        $this->assertStringNotContainsString('Quratul Ain Adnan', $html);
        $this->assertStringContainsString('Get it on Google Play', $html);
        $this->assertStringContainsString('Download on the App Store', $html);
    }
}