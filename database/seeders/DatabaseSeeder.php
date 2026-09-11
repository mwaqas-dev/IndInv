<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Release;
use App\Models\ReleaseHistory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['users.view', 'users.create', 'users.update', 'users.delete', 'users.activate', 'users.deactivate', 'users.reset-password', 'inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete', 'inventory.stock-in', 'inventory.stock-out', 'inventory.transfer', 'inventory.adjust', 'inventory.reserve', 'inventory.history', 'releases.view', 'releases.create', 'releases.update', 'releases.delete', 'releases.submit', 'releases.review', 'releases.approve', 'releases.reject', 'releases.release', 'releases.close', 'audit.view', 'activity.view', 'reports.view', 'settings.manage'];
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name], ['module' => str($name)->before('.')->headline(), 'label' => str($name)->replace('.', ' ')->headline()]);
        }
        $admin = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $manager = Role::firstOrCreate(['name' => 'manager'], ['label' => 'Manager']);
        $operator = Role::firstOrCreate(['name' => 'inventory'], ['label' => 'Inventory Operator']);
        $admin->permissions()->sync(Permission::pluck('id'));
        $manager->permissions()->sync(Permission::whereIn('name', ['inventory.view', 'inventory.history', 'releases.view', 'releases.create', 'releases.update', 'releases.submit', 'releases.review', 'releases.approve', 'releases.reject', 'releases.release', 'releases.close', 'activity.view', 'reports.view'])->pluck('id'));
        $operator->permissions()->sync(Permission::whereIn('name', ['inventory.view', 'inventory.create', 'inventory.update', 'inventory.stock-in', 'inventory.stock-out', 'inventory.transfer', 'inventory.adjust', 'inventory.reserve', 'inventory.history', 'releases.view', 'releases.create', 'releases.update', 'releases.submit', 'activity.view'])->pluck('id'));
        $adminUser = User::firstOrCreate(['email' => 'admin@forgeops.test'], ['name' => 'Amina Administrator', 'password' => 'Password123!Ab', 'is_active' => true]);
        $adminUser->roles()->sync([$admin->id]);
        $managerUser = User::firstOrCreate(['email' => 'manager@forgeops.test'], ['name' => 'Malik Manager', 'password' => 'Password123!Ab', 'is_active' => true]);
        $managerUser->roles()->sync([$manager->id]);
        $inventoryUser = User::firstOrCreate(['email' => 'inventory@forgeops.test'], ['name' => 'Ibrahim Inventory', 'password' => 'Password123!Ab', 'is_active' => true]);
        $inventoryUser->roles()->sync([$operator->id]);
        $materials = Category::firstOrCreate(['name' => 'Materials']);
        $components = Category::firstOrCreate(['name' => 'Components']);
        $main = Location::firstOrCreate(['code' => 'MAIN'], ['name' => 'Main Warehouse']);
        $line = Location::firstOrCreate(['code' => 'LINE-1'], ['name' => 'Production Line 1']);
        $steel = Product::firstOrCreate(['code' => 'MAT-10245'], ['name' => 'High-strength steel sheet', 'category_id' => $materials->id, 'unit' => 'SHT', 'minimum_stock' => 100, 'status' => 'ACTIVE', 'created_by' => $adminUser->id, 'updated_by' => $adminUser->id]);
        $valve = Product::firstOrCreate(['code' => 'CMP-20410'], ['name' => 'Pressure control valve', 'category_id' => $components->id, 'unit' => 'EA', 'minimum_stock' => 25, 'status' => 'ACTIVE', 'created_by' => $adminUser->id, 'updated_by' => $adminUser->id]);
        Inventory::firstOrCreate(['product_id' => $steel->id, 'location_id' => $main->id], ['quantity' => 540, 'reserved_quantity' => 20]);
        Inventory::firstOrCreate(['product_id' => $valve->id, 'location_id' => $line->id], ['quantity' => 18, 'reserved_quantity' => 0]);
        if (! InventoryMovement::exists()) {
            InventoryMovement::create(['product_id' => $steel->id, 'destination_location_id' => $main->id, 'type' => 'STOCK_IN', 'quantity' => 540, 'balance_after' => 540, 'reference' => 'PO-1005', 'reason' => 'Initial controlled stock', 'performed_by' => $inventoryUser->id]);
        }
        $release = Release::firstOrCreate(['number' => 'RLS-'.now()->format('Y').'-0001'], ['product_id' => $valve->id, 'project' => 'Pressure Line Upgrade', 'version' => '2.4.0', 'release_type' => 'PRODUCTION', 'description' => 'Validated pressure-control component release.', 'status' => 'UNDER_REVIEW', 'created_by' => $inventoryUser->id]);
        if (! $release->history()->exists()) {
            ReleaseHistory::create(['release_id' => $release->id, 'user_id' => $inventoryUser->id, 'action' => 'CREATED', 'new_status' => 'DRAFT']);
            ReleaseHistory::create(['release_id' => $release->id, 'user_id' => $inventoryUser->id, 'action' => 'SUBMITTED', 'previous_status' => 'DRAFT', 'new_status' => 'SUBMITTED']);
            ReleaseHistory::create(['release_id' => $release->id, 'user_id' => $managerUser->id, 'action' => 'UNDER_REVIEW', 'previous_status' => 'SUBMITTED', 'new_status' => 'UNDER_REVIEW']);
        }
        ActivityLog::firstOrCreate(['action' => 'SEED', 'module' => 'System', 'description' => 'Demo environment initialized'], ['user_id' => $adminUser->id, 'user_name' => $adminUser->name, 'result' => 'SUCCESS']);
    }
}
