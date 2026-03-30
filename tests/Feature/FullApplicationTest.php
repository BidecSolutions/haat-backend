<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Module;
use App\Models\ChatbotFaq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FullApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected string $adminToken = '';
    protected ?Admin $admin = null;
    protected ?User $createdUser = null;
    protected ?Role $adminRole = null;
    protected ?Role $userRole = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /** Step 1: Admin Login */
    public function test_step_01_admin_login(): void
    {
        $admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'testadmin@test.com',
            'password' => Hash::make('password123'),
            'status' => 1,
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => 'testadmin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonStructure(['token']);

        $this->adminToken = $response->json('token');
        $this->admin = $admin;

        $this->assertNotEmpty($this->adminToken, 'Admin token should be set');
    }

    /** Step 2: Create Permission (for admin guard) */
    public function test_step_02_create_permission(): void
    {
        $this->test_step_01_admin_login();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/create-permission', [
                'name' => 'manage.users',
                'permission_name' => 'Manage Users',
                'module_name' => 'users',
                'guard' => 'admin-api',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'manage.users');

        $this->assertDatabaseHas('permissions', ['name' => 'manage.users']);
    }

    /** Step 3: Create Admin Role */
    public function test_step_03_create_admin_role(): void
    {
        $this->test_step_02_create_permission();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/create-role', [
                'name' => 'Editor',
                'guard' => 'admin-api',
                'permissions' => ['manage.users'],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'Editor');

        $this->adminRole = Role::where('name', 'Editor')->where('guard_name', 'admin-api')->first();
        $this->assertNotNull($this->adminRole);
    }

    /** Step 4: Create Admin User */
    public function test_step_04_create_admin_user(): void
    {
        $this->test_step_03_create_admin_role();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/store', [
                'name' => 'New Admin',
                'email' => 'newadmin@test.com',
                'password' => 'password123',
                'phone' => '1234567890',
                'status' => 1,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.email', 'newadmin@test.com');

        $this->assertDatabaseHas('admins', ['email' => 'newadmin@test.com']);
    }

    /** Step 5: Assign Role to Admin */
    public function test_step_05_assign_role_to_admin(): void
    {
        $this->test_step_04_create_admin_user();

        $admin = Admin::where('email', 'newadmin@test.com')->first();
        $role = Role::where('name', 'Editor')->where('guard_name', 'admin-api')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/assign-role-admin', [
                'admin_id' => $admin->id,
                'role' => 'Editor',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $admin->refresh();
        $this->assertTrue($admin->hasRole('Editor'));
    }

    /** Step 6: Create User Role (for marketplace users) */
    public function test_step_06_create_user_role(): void
    {
        $this->test_step_05_assign_role_to_admin();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/create-role', [
                'name' => 'Seller',
                'guard' => 'api',
                'permissions' => [],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'Seller');

        $this->userRole = Role::where('name', 'Seller')->where('guard_name', 'api')->first();
        $this->assertNotNull($this->userRole);
    }

    /** Step 7: Create User (marketplace user via admin) */
    public function test_step_07_create_user(): void
    {
        $this->test_step_06_create_user_role();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/user/store', [
                'name' => 'Test Seller',
                'email' => 'testseller@test.com',
                'password' => 'password123',
                'phone' => '9876543210',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.email', 'testseller@test.com');

        $this->createdUser = User::where('email', 'testseller@test.com')->first();
        $this->assertNotNull($this->createdUser);
    }

    /** Step 8: Assign Role to User */
    public function test_step_08_assign_role_to_user(): void
    {
        $this->test_step_07_create_user();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/assign-role-user', [
                'user_id' => $this->createdUser->id,
                'role' => 'Seller',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->createdUser->refresh();
        $this->assertTrue($this->createdUser->hasRole('Seller'));
    }

    /** Step 9: List Roles */
    public function test_step_09_list_roles(): void
    {
        $this->test_step_08_assign_role_to_user();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/roles');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['data' => [['id', 'name', 'guard_name', 'permissions']]]);
    }

    /** Step 10: List Users */
    public function test_step_10_list_users(): void
    {
        $this->test_step_08_assign_role_to_user();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/user');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['data']);
    }

    /** Step 11: Dashboard Stats */
    public function test_step_11_dashboard_stats(): void
    {
        $this->test_step_01_admin_login();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(200);
    }

    /** Step 12: List Categories (Admin) */
    public function test_step_12_list_categories(): void
    {
        $this->test_step_01_admin_login();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/category/');

        $response->assertStatus(200);
    }

    /** Step 13: List Modules */
    public function test_step_13_list_modules(): void
    {
        $this->test_step_01_admin_login();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/modules/');

        $response->assertStatus(200);
    }

    /** Step 14: List Chatbot FAQs */
    public function test_step_14_list_chatbot_faqs(): void
    {
        $this->test_step_01_admin_login();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/chatbot/');

        $response->assertStatus(200);
    }

    /** Step 15: Public - Get Categories (no auth) */
    public function test_step_15_public_categories(): void
    {
        $response = $this->getJson('/api/category');

        $response->assertStatus(200);
    }

    /** Step 16: Public - Get Listings (no auth) */
    public function test_step_16_public_listings(): void
    {
        $response = $this->getJson('/api/listings');

        $response->assertStatus(200);
    }

    /** Step 17: Public - Chatbot FAQs */
    public function test_step_17_public_chatbot_faqs(): void
    {
        $response = $this->getJson('/api/chatbot/faqs');

        $response->assertStatus(200);
    }

    /** Step 18: Admin Profile */
    public function test_step_18_admin_profile(): void
    {
        $this->test_step_01_admin_login();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/profile');

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
    }

    /** Step 19: Admin Logout */
    public function test_step_19_admin_logout(): void
    {
        $this->test_step_01_admin_login();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/logout');

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
    }
}
