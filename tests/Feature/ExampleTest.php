<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\Release;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\ReleaseWorkflowService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_open_dashboard_and_operator_cannot_open_users(): void
    {
        $admin = User::where('email', 'admin@forgeops.test')->firstOrFail();
        $operator = User::where('email', 'inventory@forgeops.test')->firstOrFail();
        $this->actingAs($admin)->get('/')->assertOk();
        $this->actingAs($operator)->get('/users')->assertForbidden();
    }

    public function test_inventory_service_records_balance_and_prevents_negative_stock(): void
    {
        $user = User::where('email', 'inventory@forgeops.test')->firstOrFail();
        $product = Product::where('code', 'MAT-10245')->firstOrFail();
        $location = Location::where('code', 'MAIN')->firstOrFail();
        $service = app(InventoryService::class);
        $service->stockIn($product, $location->id, 10, 'Receipt', 'TEST-1', null, $user);
        $this->assertSame('550.000', Inventory::where(['product_id' => $product->id, 'location_id' => $location->id])->first()->quantity);
        $this->expectException(ValidationException::class);
        $service->stockOut($product, $location->id, 9999, 'Invalid issue', null, null, $user);
    }

    public function test_release_rejection_requires_comment(): void
    {
        $admin = User::where('email', 'admin@forgeops.test')->firstOrFail();
        $release = Release::where('status', 'UNDER_REVIEW')->firstOrFail();
        $this->expectException(ValidationException::class);
        app(ReleaseWorkflowService::class)->transition($release, 'REJECTED', $admin, null);
    }

    public function test_inventory_submission_requires_approval_but_manager_submission_is_audited_as_approval_not_required(): void
    {
        $operator = User::where('email', 'inventory@forgeops.test')->firstOrFail();
        $manager = User::where('email', 'manager@forgeops.test')->firstOrFail();
        $operatorRelease = Release::create(['number' => 'RLS-TEST-OP', 'project' => 'Test', 'version' => '1', 'release_type' => 'MAINTENANCE', 'description' => 'Test', 'status' => 'DRAFT', 'created_by' => $operator->id]);
        app(ReleaseWorkflowService::class)->transition($operatorRelease, 'SUBMITTED', $operator);
        $this->assertSame('SUBMITTED', $operatorRelease->fresh()->status);
        $managerRelease = Release::create(['number' => 'RLS-TEST-MGR', 'project' => 'Test', 'version' => '1', 'release_type' => 'MAINTENANCE', 'description' => 'Test', 'status' => 'DRAFT', 'created_by' => $manager->id]);
        app(ReleaseWorkflowService::class)->transition($managerRelease, 'SUBMITTED', $manager);
        $this->assertSame('APPROVED', $managerRelease->fresh()->status);
        $this->assertDatabaseHas('release_history',['release_id' => $managerRelease->id, 'action' => 'APPROVAL_NOT_REQUIRED']);
    }
}
