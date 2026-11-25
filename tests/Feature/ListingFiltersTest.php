<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\TicketCategory;
use App\Models\TicketType;
use App\Models\User;
use App\Support\ListingFilters;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ListingFiltersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['departments', 'designations', 'grades', 'ticket_types'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->string('name'); $table->timestamps();
            });
        }
        Schema::create('regions', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->unsignedBigInteger('p_region_id')->nullable(); $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('user_id')->nullable();
            $table->string('user_type')->default('user'); $table->timestamps();
        });
        require_once database_path('migrations/2023_05_02_180827_create_permission_tables.php');
        (new \CreatePermissionTables())->up();
        foreach (['user_separation', 'employee_separation'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->string('name'); $table->string('email'); $table->string('employee_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable(); $table->unsignedBigInteger('designation_id')->nullable();
                $table->date('separation_date')->nullable(); $table->timestamps();
            });
        }
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('code'); $table->unsignedBigInteger('ticket_type_id')->nullable(); $table->timestamps();
        });
        Schema::create('ticket_sub_types', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('priority')->nullable(); $table->unsignedBigInteger('ticket_category_id')->nullable(); $table->timestamps();
        });
        Schema::create('ticket_settings', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('ticket_type_id'); $table->unsignedBigInteger('ticket_sub_type_id'); $table->timestamps();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::create(['name' => 'Test Admin', 'email' => 'admin@example.test']);
        $user->assignRole(Role::create(['name' => 'superAdmin', 'guard_name' => 'web']));
        $this->actingAs($user);
    }

    public function test_search_covers_records_outside_the_first_page_and_survives_reload(): void
    {
        for ($i = 1; $i <= 14; $i++) Department::create(['name' => 'Department '.$i]);
        $url = '/departments?search=Department%2014&perpage=5';
        foreach ([1, 2] as $reload) {
            $request = Request::create($url);
            $response = app(\App\Http\Controllers\DepartmentController::class)->index($request);
            $rows = $response->getData()['departments'];
            $this->assertSame(1, $rows->total());
            $this->assertSame('Department 14', $rows->first()->name);
            $this->assertSame(5, $rows->perPage());
        }
    }

    public function test_pagination_retains_search_page_size_and_other_filters(): void
    {
        for ($i = 1; $i <= 12; $i++) Department::create(['name' => 'Operations '.$i]);
        $request = Request::create('/departments?search=Operations&perpage=5&tab=active');
        $rows = ListingFilters::paginate(Department::query(), $request, ['name']);
        parse_str(parse_url($rows->url(2), PHP_URL_QUERY), $query);
        $this->assertEquals(['search' => 'Operations', 'perpage' => '5', 'tab' => 'active', 'page' => '2'], $query);
        $this->assertSame(12, $rows->total());
        $this->assertSame(5, $rows->count());
    }

    public function test_related_search_is_grouped_inside_existing_access_scope(): void
    {
        $type = TicketType::create(['name' => 'Private Type']);
        TicketCategory::create(['name' => 'Visible', 'code' => 'VIS', 'ticket_type_id' => $type->id]);
        TicketCategory::create(['name' => 'Hidden', 'code' => 'HID', 'ticket_type_id' => $type->id]);
        $request = Request::create('/ticketcategories?search=Private');
        $rows = ListingFilters::paginate(TicketCategory::where('code', 'VIS'), $request, ['name'], ['ticketType' => ['name']]);
        $this->assertSame(['Visible'], $rows->pluck('name')->all());
    }

    public function test_invalid_page_sizes_and_non_scalar_search_are_handled_safely(): void
    {
        foreach (['0', '-1', '999999', 'banana', ['10']] as $input) {
            $request = Request::create('/', 'GET', ['perpage' => $input, 'search' => ['invalid']]);
            $this->assertSame(10, ListingFilters::perPage($request));
            $this->assertSame('', ListingFilters::search($request));
        }
        $this->assertSame(200, mb_strlen(ListingFilters::search(Request::create('/', 'GET', ['search' => str_repeat('a', 300)]))));
    }

    public function test_all_option_and_no_results_are_valid(): void
    {
        for ($i = 0; $i < 12; $i++) Department::create(['name' => 'Sales '.$i]);
        $all = ListingFilters::paginate(Department::query(), Request::create('/?perpage=all'), ['name']);
        $this->assertSame(12, $all->count());
        $empty = ListingFilters::paginate(Department::query(), Request::create('/?perpage=all&search=Missing'), ['name']);
        $this->assertSame(0, $empty->total());
        $this->assertSame(1, $empty->perPage());
    }

    public function test_admin_listing_controllers_use_shared_filters(): void
    {
        $cases = [
            [\App\Http\Controllers\DesignationController::class, 'index', 'designations', 'designations'],
            [\App\Http\Controllers\GradeController::class, 'index', 'grades', 'grades'],
            [\App\Http\Controllers\RegionController::class, 'index', 'regions', 'regions'],
            [\App\Http\Controllers\TicketTypeController::class, 'index', 'tickettypes', 'ticket_types'],
            [\App\Http\Controllers\UserController::class, 'userSeparationIndex', 'userSeparations', 'user_separation'],
            [\App\Http\Controllers\EmployeesController::class, 'employeeSeparationIndex', 'employeeSeparations', 'employee_separation'],
        ];
        foreach ($cases as [$controller, $method, $variable, $table]) {
            DB::table($table)->insert(['name' => 'Unique Match', ... (str_contains($table, 'separation') ? ['email' => 'match@example.test'] : [])]);
            DB::table($table)->insert(['name' => 'Other', ... (str_contains($table, 'separation') ? ['email' => 'other@example.test'] : [])]);
            $view = app($controller)->$method(Request::create('/?search=Unique&perpage=5'));
            $this->assertSame(1, $view->getData()[$variable]->total(), $controller);
            $this->assertSame(5, $view->getData()[$variable]->perPage());
        }
        foreach ([
            [\App\Http\Controllers\TicketSubTypeController::class, 'ticketsubtypes'],
            [\App\Http\Controllers\TicketSettingController::class, 'ticketsettings'],
            [\App\Http\Controllers\TicketCategoryController::class, 'categories'],
            [\App\Http\Controllers\RolesPermissionController::class, 'roles'],
            [\App\Http\Controllers\UserController::class, 'users'],
        ] as [$controller, $variable]) {
            $view = app($controller)->index(Request::create('/?search=Missing&perpage=20'));
            $this->assertSame(0, $view->getData()[$variable]->total(), $controller);
            $this->assertSame(20, $view->getData()[$variable]->perPage());
        }
    }

    public function test_shared_controls_preserve_values_and_reset_pagination(): void
    {
        $this->app->instance('request', Request::create('/departments?search=Finance&perpage=20&page=3&tab=active'));
        $html = view('listings.filters', ['listingLabel' => 'Departments'])->render();
        $this->assertStringContainsString('value="Finance"', $html);
        $this->assertStringContainsString('value="20" selected', $html);
        $this->assertStringContainsString('name="tab" value="active"', $html);
        $this->assertStringNotContainsString('name="page"', $html);
        $this->assertStringContainsString('>Reset</a>', $html);
        $this->assertStringContainsString('>Search</button>', $html);
    }
}
