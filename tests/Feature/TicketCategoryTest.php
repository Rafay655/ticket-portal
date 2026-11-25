<?php

namespace Tests\Feature;

use App\Models\TicketCategory;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\TicketCategoryPermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketCategoryTest extends TestCase
{
    private User $admin;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password')->nullable(); $table->timestamps();
        });
        Schema::create('abouts', function (Blueprint $table) {
            $table->id(); $table->string('name');
        });
        DB::table('abouts')->insert(['name' => 'Flexi Desk']);
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->string('type'); $table->morphs('notifiable');
            $table->text('data'); $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        require_once database_path('migrations/2023_05_02_180827_create_permission_tables.php');
        (new \CreatePermissionTables())->up();
        $migration = require database_path('migrations/2026_11_25_000001_create_ticket_categories_table.php');
        $migration->up();
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps();
        });
        DB::table('ticket_types')->insert(['id' => 1, 'name' => 'JazzCash']);
        Schema::create('ticket_sub_types', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('priority')->nullable(); $table->timestamps();
        });
        Schema::create('tickets', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tickettype_id'); $table->unsignedBigInteger('ticketsubtype_id'); $table->timestamps();
        });
        $hierarchy = require database_path('migrations/2026_11_25_000002_add_ticket_hierarchy_relationships.php');
        $hierarchy->up();
        foreach (['index', 'create', 'edit', 'delete', 'view'] as $action) {
            Permission::create(['name' => 'ticketCategory.'.$action, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = User::create(['name' => 'Category Admin', 'email' => 'admin@example.com']);
        $this->admin->givePermissionTo(Permission::all());
        $this->viewer = User::create(['name' => 'Category Viewer', 'email' => 'viewer@example.com']);
        $this->viewer->givePermissionTo('ticketCategory.index');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_create_category_with_automatic_timestamps(): void
    {
        Carbon::setTestNow('2026-11-25 12:00:00');
        $this->actingAs($this->admin)->post(route('ticketcategories.store'), ['ticket_type_id' => 1, 'name' => ' Facilities ', 'code' => ' fac-001 '])
            ->assertRedirect(route('ticketcategories.index'))->assertSessionHas('success', 'Create successfully!');
        $category = TicketCategory::first();
        $this->assertSame('Facilities', $category->name);
        $this->assertSame('FAC-001', $category->code);
        $this->assertSame('2026-11-25 12:00:00', $category->created_at->toDateTimeString());
        $this->assertTrue($category->created_at->equalTo($category->updated_at));
    }

    public function test_admin_can_view_categories_with_codes_dates_and_navigation(): void
    {
        TicketCategory::create(['ticket_type_id' => 1, 'name' => 'Facilities', 'code' => 'FAC-001']);
        $this->actingAs($this->admin)->get(route('ticketcategories.index'))->assertOk()
            ->assertSee('Facilities')->assertSee('FAC-001')->assertSee('Created At')->assertSee('Updated At')
            ->assertSee(route('ticketcategories.index'), false)->assertSee('sweet_alerts.js', false);
    }

    public function test_category_forms_render_and_preserve_values(): void
    {
        $category = TicketCategory::create(['ticket_type_id' => 1, 'name' => 'Facilities', 'code' => 'FAC-001']);
        $this->actingAs($this->admin)->get(route('ticketcategories.create'))->assertOk()->assertSee('Create New Ticket Category');
        $this->get(route('ticketcategories.edit', $category))->assertOk()->assertSee('FAC-001')->assertSee('Updated At');
    }

    public function test_update_changes_updated_at_and_preserves_created_at(): void
    {
        Carbon::setTestNow('2026-11-25 12:00:00');
        $category = TicketCategory::create(['ticket_type_id' => 1, 'name' => 'Facilities', 'code' => 'FAC-001']);
        Carbon::setTestNow('2026-11-25 12:15:00');
        $this->actingAs($this->admin)->put(route('ticketcategories.update', $category), ['ticket_type_id' => 1, 'name' => 'Office Facilities', 'code' => 'FAC-001', 'created_at' => '2000-01-01', 'updated_at' => '2000-01-01'])
            ->assertRedirect(route('ticketcategories.index'))->assertSessionHas('success', 'Update successfully!');
        $category->refresh();
        $this->assertSame('Office Facilities', $category->name);
        $this->assertSame('2026-11-25 12:00:00', $category->created_at->toDateTimeString());
        $this->assertSame('2026-11-25 12:15:00', $category->updated_at->toDateTimeString());
    }

    public function test_duplicate_code_is_rejected_after_case_normalization(): void
    {
        TicketCategory::create(['ticket_type_id' => 1, 'name' => 'Facilities', 'code' => 'FAC-001']);
        $this->actingAs($this->admin)->postJson(route('ticketcategories.store'), ['ticket_type_id' => 1, 'name' => 'Other', 'code' => 'fac-001'])
            ->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame(1, TicketCategory::count());
    }

    public function test_update_cannot_use_another_category_code(): void
    {
        $one = TicketCategory::create(['ticket_type_id' => 1, 'name' => 'One', 'code' => 'ONE']);
        TicketCategory::create(['ticket_type_id' => 1, 'name' => 'Two', 'code' => 'TWO']);
        $this->actingAs($this->admin)->putJson(route('ticketcategories.update', $one), ['ticket_type_id' => 1, 'name' => 'One', 'code' => 'TWO'])
            ->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame('ONE', $one->fresh()->code);
    }

    public function test_required_fields_and_length_limits_are_validated(): void
    {
        $this->actingAs($this->admin)->postJson(route('ticketcategories.store'), ['ticket_type_id' => 1, 'name' => ' ', 'code' => ' '])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'code']);
        $this->postJson(route('ticketcategories.store'), ['ticket_type_id' => 1, 'name' => str_repeat('A', 256), 'code' => str_repeat('B', 51)])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'code']);
        $this->assertSame(0, TicketCategory::count());
    }

    public function test_admin_can_delete_with_a_success_modal_after_redirect(): void
    {
        $category = TicketCategory::create(['ticket_type_id' => 1, 'name' => 'Facilities', 'code' => 'FAC-001']);
        $this->actingAs($this->admin)->delete(route('ticketcategories.destroy', $category))
            ->assertRedirect(route('ticketcategories.index'))->assertSessionHas('success', 'Ticket category has been deleted');
        $this->assertSame(0, TicketCategory::count());
    }

    public function test_ajax_delete_retains_the_shared_delete_response_contract(): void
    {
        $category = TicketCategory::create(['ticket_type_id' => 1, 'name' => 'Facilities', 'code' => 'FAC-001']);
        $this->actingAs($this->admin)->deleteJson(route('ticketcategories.destroy', $category))
            ->assertOk()->assertExactJson(['deleted', 'Ticket category has been deleted']);
        $this->assertSame(0, TicketCategory::count());
    }

    public function test_search_matches_name_or_code_and_pagination_preserves_search(): void
    {
        foreach (range(1, 7) as $number) TicketCategory::create(['ticket_type_id' => 1, 'name' => 'Facilities '.$number, 'code' => 'FAC-'.$number]);
        TicketCategory::create(['ticket_type_id' => 1, 'name' => 'Payroll', 'code' => 'PAY']);
        $response = $this->actingAs($this->admin)->get(route('ticketcategories.index', ['search' => 'FAC', 'perpage' => 5]))
            ->assertOk()->assertViewHas('categories', fn ($categories) => $categories->total() === 7 && $categories->count() === 5);
        $this->assertStringContainsString('search=FAC', $response->viewData('categories')->url(2));
        $this->get(route('ticketcategories.index', ['search' => 'PAY']))->assertOk()
            ->assertViewHas('categories', fn ($categories) => $categories->total() === 1 && $categories->first()->code === 'PAY');
    }

    public function test_viewer_can_read_but_cannot_create_edit_or_delete(): void
    {
        $category = TicketCategory::create(['ticket_type_id' => 1, 'name' => 'Facilities', 'code' => 'FAC-001']);
        $this->actingAs($this->viewer)->get(route('ticketcategories.index'))->assertOk()->assertDontSee('Add New');
        $this->get(route('ticketcategories.create'))->assertForbidden();
        $this->postJson(route('ticketcategories.store'), ['ticket_type_id' => 1, 'name' => 'Other', 'code' => 'OTHER'])->assertForbidden();
        $this->get(route('ticketcategories.edit', $category))->assertForbidden();
        $this->putJson(route('ticketcategories.update', $category), ['ticket_type_id' => 1, 'name' => 'Other', 'code' => 'OTHER'])->assertForbidden();
        $this->deleteJson(route('ticketcategories.destroy', $category))->assertForbidden();
        $this->assertSame(1, TicketCategory::count());
    }

    public function test_guests_and_users_without_category_permission_cannot_access_categories(): void
    {
        $this->get(route('ticketcategories.index'))->assertRedirect(route('login'));
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com']);
        $this->actingAs($other)->get(route('ticketcategories.index'))->assertForbidden();
    }

    public function test_unknown_category_returns_not_found(): void
    {
        $this->actingAs($this->admin)->get(route('ticketcategories.edit', 999))->assertNotFound();
    }

    public function test_permission_seeder_is_additive_idempotent_and_preserves_role_access(): void
    {
        Permission::create(['name' => 'ticketType.index', 'guard_name' => 'web']);
        Permission::create(['name' => 'ticketType.edit', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Ticket Editor', 'guard_name' => 'web']);
        $role->givePermissionTo(['ticketType.index', 'ticketType.edit']);
        $super = Role::create(['name' => 'superAdmin', 'guard_name' => 'web']);
        $regular = Role::create(['name' => 'Employee', 'guard_name' => 'web']);
        $this->seed(TicketCategoryPermissionSeeder::class);
        $this->seed(TicketCategoryPermissionSeeder::class);
        $this->assertTrue($role->fresh()->hasPermissionTo('ticketCategory.index'));
        $this->assertTrue($role->fresh()->hasPermissionTo('ticketCategory.edit'));
        $this->assertFalse($role->fresh()->hasPermissionTo('ticketCategory.delete'));
        $this->assertTrue($super->fresh()->hasPermissionTo('ticketCategory.delete'));
        $this->assertFalse($regular->fresh()->hasPermissionTo('ticketCategory.index'));
        $this->assertSame(5, Permission::where('name', 'like', 'ticketCategory.%')->count());
    }
    public function test_serial_numbers_continue_on_the_second_listing_page(): void
    {
        foreach (range(1, 7) as $number) TicketCategory::create(['ticket_type_id' => 1, 'name' => 'Category '.$number, 'code' => 'CAT-'.$number]);
        $response = $this->actingAs($this->admin)->get(route('ticketcategories.index', ['perpage' => 5, 'page' => 2]))
            ->assertOk()->assertSee('Sr#');
        preg_match_all('/<tbody>\s*<tr>\s*<td>\s*(\d+)\s*<\/td>|<\/tr>\s*<tr>\s*<td>\s*(\d+)\s*<\/td>/s', $response->getContent(), $matches, PREG_SET_ORDER);
        $numbers = array_map(fn ($match) => (int) (($match[1] ?? '') !== '' ? $match[1] : $match[2]), $matches);
        $this->assertSame([6, 7], $numbers);
    }

    public function test_super_admin_can_access_categories_without_explicit_category_permissions(): void
    {
        $role = Role::create(['name' => 'superAdmin', 'guard_name' => 'web']);
        $this->viewer->syncPermissions([]);
        $this->viewer->assignRole($role);
        $this->actingAs($this->viewer)->get(route('ticketcategories.index'))->assertOk();
        $this->assertTrue($this->viewer->can('ticketCategory.create'));
        $this->assertTrue($this->viewer->can('future.permission'));
    }

    public function test_regular_users_do_not_receive_super_admin_bypass(): void
    {
        Role::create(['name' => 'superAdmin', 'guard_name' => 'web']);
        $this->assertFalse($this->viewer->can('ticketCategory.create'));
        $this->assertFalse($this->viewer->can('future.permission'));
        $this->actingAs($this->viewer)->get(route('ticketcategories.create'))->assertForbidden();
    }

    public function test_super_admin_permission_repair_is_additive_and_does_not_change_other_roles(): void
    {
        $super = Role::create(['name' => 'superAdmin', 'guard_name' => 'web']);
        $other = Role::create(['name' => 'restricted', 'guard_name' => 'web']);
        $other->givePermissionTo('ticketCategory.index');
        $this->seed(\Database\Seeders\SuperAdminPermissionSeeder::class);
        $this->seed(\Database\Seeders\SuperAdminPermissionSeeder::class);
        $this->assertSame(Permission::where('guard_name', 'web')->count(), $super->fresh()->permissions()->count());
        $this->assertSame(['ticketCategory.index'], $other->fresh()->permissions->pluck('name')->all());
    }
}