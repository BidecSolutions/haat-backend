<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RunApiTests extends Command
{
    protected $signature = 'test:api {--base=http://127.0.0.1:8000 : Base URL of the API}';
    protected $description = 'Run step-by-step API tests against the running application';

    protected string $baseUrl;
    protected ?string $adminToken = null;
    protected ?int $createdUserId = null;
    protected array $results = [];

    public function handle(): int
    {
        $this->baseUrl = rtrim($this->option('base') ?? 'http://127.0.0.1:8000', '/');
        $this->info("Testing API at: {$this->baseUrl}/api");
        $this->newLine();

        $tests = [
            ['Step 1', 'Admin Login', fn () => $this->testAdminLogin()],
            ['Step 2', 'Create Permission', fn () => $this->testCreatePermission()],
            ['Step 3', 'Create Admin Role', fn () => $this->testCreateAdminRole()],
            ['Step 4', 'Create Admin User', fn () => $this->testCreateAdminUser()],
            ['Step 5', 'Assign Role to Admin', fn () => $this->testAssignRoleToAdmin()],
            ['Step 6', 'Create User Role', fn () => $this->testCreateUserRole()],
            ['Step 7', 'Create User', fn () => $this->testCreateUser()],
            ['Step 8', 'Assign Role to User', fn () => $this->testAssignRoleToUser()],
            ['Step 9', 'List Roles', fn () => $this->testListRoles()],
            ['Step 10', 'List Users', fn () => $this->testListUsers()],
            ['Step 11', 'Dashboard Stats', fn () => $this->testDashboardStats()],
            ['Step 12', 'List Categories', fn () => $this->testListCategories()],
            ['Step 13', 'List Modules', fn () => $this->testListModules()],
            ['Step 14', 'List Chatbot FAQs', fn () => $this->testListChatbotFaqs()],
            ['Step 15', 'Public Categories', fn () => $this->testPublicCategories()],
            ['Step 16', 'Public Listings', fn () => $this->testPublicListings()],
            ['Step 17', 'Public Chatbot FAQs', fn () => $this->testPublicChatbotFaqs()],
            ['Step 18', 'Admin Profile', fn () => $this->testAdminProfile()],
            ['Step 19', 'Admin Logout', fn () => $this->testAdminLogout()],
        ];

        foreach ($tests as [$step, $name, $fn]) {
            $this->runTest($step, $name, $fn);
        }

        $this->printReport();
        return $this->hasFailures() ? 1 : 0;
    }

    protected function runTest(string $step, string $name, callable $fn): void
    {
        try {
            $fn();
            $this->results[] = ['step' => $step, 'name' => $name, 'passed' => true, 'message' => 'OK'];
            $this->line("  <fg=green>✓</> {$step}: {$name}");
        } catch (\Throwable $e) {
            $this->results[] = ['step' => $step, 'name' => $name, 'passed' => false, 'message' => $e->getMessage()];
            $this->line("  <fg=red>✗</> {$step}: {$name} - " . $e->getMessage());
        }
    }

    protected function testAdminLogin(): void
    {
        $r = Http::post("{$this->baseUrl}/api/admin/login", [
            'email' => 'admin@admin.com',
            'password' => '123123',
        ]);

        if ($r->status() !== 200) {
            throw new \RuntimeException("Login failed: " . ($r->json('message') ?? $r->body()));
        }
        $data = $r->json();
        if (empty($data['token'])) {
            throw new \RuntimeException("No token in response");
        }
        $this->adminToken = $data['token'];
    }

    protected function testCreatePermission(): void
    {
        $name = 'test.perm.' . time();
        $r = $this->post("/api/admin/create-permission", [
            'name' => $name,
            'permission_name' => 'Test Permission',
            'module_name' => 'test',
            'guard' => 'admin-api',
        ]);

        if ($r->status() !== 201) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testCreateAdminRole(): void
    {
        $r = $this->post("/api/admin/create-role", [
            'name' => 'TestRole' . time(),
            'guard' => 'admin-api',
            'permissions' => [],
        ]);

        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testCreateAdminUser(): void
    {
        $email = 'newadmin' . time() . '@test.com';
        $r = $this->post("/api/admin/store", [
            'name' => 'New Admin',
            'email' => $email,
            'password' => 'password123',
            'status' => 1,
        ]);

        if ($r->status() !== 201) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
        $this->createdAdminId = $r->json('data.id');
    }

    protected function testAssignRoleToAdmin(): void
    {
        $roles = $this->get("/api/admin/roles")->json('data');
        $role = $roles[0]['name'] ?? null;
        $admins = $this->get("/api/admin/list")->json('data');
        $admin = collect($admins)->last();

        if (!$role || !$admin) {
            throw new \RuntimeException("No role or admin to assign");
        }

        $r = $this->post("/api/admin/assign-role-admin", [
            'admin_id' => $admin['id'],
            'role' => $role,
        ]);

        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testCreateUserRole(): void
    {
        $r = $this->post("/api/admin/create-role", [
            'name' => 'Seller' . time(),
            'guard' => 'api',
            'permissions' => [],
        ]);

        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testCreateUser(): void
    {
        $email = 'testseller' . time() . '@test.com';
        $r = $this->post("/api/admin/user/store", [
            'name' => 'Test Seller',
            'email' => $email,
            'password' => 'password123',
        ]);

        if ($r->status() !== 201) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
        $this->createdUserId = $r->json('data.id');
    }

    protected function testAssignRoleToUser(): void
    {
        $roles = collect($this->get("/api/admin/roles")->json('data'))
            ->filter(fn ($r) => $r['guard_name'] === 'api')->values();
        $role = $roles->first()['name'] ?? null;
        $users = $this->get("/api/admin/user")->json('data');
        $user = collect($users)->last();

        if (!$role || !$user) {
            throw new \RuntimeException("No user role or user to assign");
        }

        $r = $this->post("/api/admin/assign-role-user", [
            'user_id' => $user['id'],
            'role' => $role,
        ]);

        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testListRoles(): void
    {
        $r = $this->get("/api/admin/roles");
        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testListUsers(): void
    {
        $r = $this->get("/api/admin/user");
        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testDashboardStats(): void
    {
        $r = $this->get("/api/admin/dashboard/stats");
        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testListCategories(): void
    {
        $r = $this->get("/api/admin/category/");
        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testListModules(): void
    {
        $r = $this->get("/api/admin/modules/");
        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testListChatbotFaqs(): void
    {
        $r = $this->get("/api/admin/chatbot/");
        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testPublicCategories(): void
    {
        $r = Http::get("{$this->baseUrl}/api/category");
        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testPublicListings(): void
    {
        $r = Http::get("{$this->baseUrl}/api/listings");
        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testPublicChatbotFaqs(): void
    {
        $r = Http::get("{$this->baseUrl}/api/chatbot/faqs");
        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testAdminProfile(): void
    {
        $r = $this->get("/api/admin/profile");
        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function testAdminLogout(): void
    {
        $r = $this->post("/api/admin/logout", []);
        if ($r->status() !== 200) {
            throw new \RuntimeException($r->json('message') ?? $r->body());
        }
    }

    protected function get(string $path)
    {
        return Http::withToken($this->adminToken)->get($this->baseUrl . $path);
    }

    protected function post(string $path, array $data)
    {
        return Http::withToken($this->adminToken)->post($this->baseUrl . $path, $data);
    }

    protected function hasFailures(): bool
    {
        return collect($this->results)->contains(fn ($r) => !$r['passed']);
    }

    protected function printReport(): void
    {
        $this->newLine(2);
        $this->info('========== TEST REPORT ==========');
        $passed = collect($this->results)->filter(fn ($r) => $r['passed'])->count();
        $failed = collect($this->results)->filter(fn ($r) => !$r['passed'])->count();
        $this->table(
            ['Step', 'Test Case', 'Status', 'Message'],
            collect($this->results)->map(fn ($r) => [
                $r['step'],
                $r['name'],
                $r['passed'] ? 'PASSED' : 'FAILED',
                $r['passed'] ? '-' : $r['message'],
            ])->toArray()
        );
        $this->newLine();
        $this->info("Total: " . count($this->results) . " | Passed: {$passed} | Failed: {$failed}");
        $this->info('==================================');
    }
}
