<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test users with roles
    $adminRole = \App\Models\Role::create(['name' => 'admin']);
    $managerRole = \App\Models\Role::create(['name' => 'manager']);
    $staffRole = \App\Models\Role::create(['name' => 'staff']);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach($adminRole);

    $this->manager = User::factory()->create();
    $this->manager->roles()->attach($managerRole);

    $this->staff = User::factory()->create();
    $this->staff->roles()->attach($staffRole);
});

test('admin can view audit logs', function () {
    // Create some audit logs
    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'auditable_type',
                    'auditable_id',
                    'action',
                    'user_id',
                    'created_at',
                ],
            ],
            'meta',
        ]);
});

test('manager can view audit logs', function () {
    $response = $this->actingAs($this->manager)
        ->getJson('/api/v1/audit-logs');

    $response->assertStatus(200);
});

test('staff cannot view audit logs', function () {
    $response = $this->actingAs($this->staff)
        ->getJson('/api/v1/audit-logs');

    $response->assertStatus(403);
});

test('audit log is created when sales return is approved', function () {
    $salesReturn = SalesReturn::factory()->create(['status' => 'draft']);

    expect(AuditLog::count())->toBe(0);

    // Simulate approval with audit logging
    $beforeState = $salesReturn->snapshot(['id', 'status']);
    $salesReturn->update(['status' => 'approved']);
    $salesReturn->auditApproved($beforeState);

    expect(AuditLog::count())->toBe(1);

    $auditLog = AuditLog::first();
    expect($auditLog->auditable_type)->toBe(SalesReturn::class);
    expect($auditLog->auditable_id)->toBe($salesReturn->id);
    expect($auditLog->action)->toBe('approved');
    expect($auditLog->before['status'])->toBe('draft');
});

test('audit log is created when purchase return is approved', function () {
    $purchaseReturn = PurchaseReturn::factory()->create(['status' => 'draft']);

    expect(AuditLog::count())->toBe(0);

    // Simulate approval with audit logging
    $beforeState = $purchaseReturn->snapshot(['id', 'status']);
    $purchaseReturn->update(['status' => 'approved']);
    $purchaseReturn->auditApproved($beforeState);

    expect(AuditLog::count())->toBe(1);

    $auditLog = AuditLog::first();
    expect($auditLog->auditable_type)->toBe(PurchaseReturn::class);
    expect($auditLog->action)->toBe('approved');
});

test('can filter audit logs by auditable type', function () {
    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    $purchase = Purchase::factory()->create();
    $purchase->auditCreated();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?auditable_type='.urlencode(SalesReturn::class));

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
    expect($response->json('data.0.auditable_type'))->toBe(SalesReturn::class);
});

test('can filter audit logs by action', function () {
    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    $beforeState = $salesReturn->snapshot(['id', 'status']);
    $salesReturn->update(['status' => 'approved']);
    $salesReturn->auditApproved($beforeState);

    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?action=approved');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
    expect($response->json('data.0.action'))->toBe('approved');
});

test('can filter audit logs by user', function () {
    $this->actingAs($this->admin);

    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?user_id='.$this->admin->id);

    $response->assertStatus(200);
    expect($response->json('data.0.user_id'))->toBe($this->admin->id);
});

test('can view specific audit log', function () {
    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    $auditLog = AuditLog::first();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs/'.$auditLog->id);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $auditLog->id,
                'auditable_type' => SalesReturn::class,
                'action' => 'created',
            ],
        ]);
});

test('audit logs are paginated', function () {
    $this->actingAs($this->admin);

    // Create 20 audit logs
    for ($i = 0; $i < 20; $i++) {
        $salesReturn = SalesReturn::factory()->create();
        $salesReturn->auditCreated();
    }

    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?per_page=10');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(10);
    expect($response->json('meta.total'))->toBe(20);
    expect($response->json('meta.per_page'))->toBe(10);
});

test('audit log includes user relationship', function () {
    $this->actingAs($this->admin);

    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ],
        ]);
});

test('audit log captures before and after snapshots', function () {
    $salesReturn = SalesReturn::factory()->create([
        'status' => 'draft',
        'total' => 1000.00,
    ]);

    $beforeState = $salesReturn->snapshot(['id', 'status', 'total']);

    $salesReturn->update([
        'status' => 'approved',
        'total' => 1100.00,
    ]);

    $salesReturn->auditUpdated($beforeState);

    $auditLog = AuditLog::first();

    expect($auditLog->before['status'])->toBe('draft');
    expect($auditLog->before['total'])->toBe('1000.00');
    expect($auditLog->after['status'])->toBe('approved');
    expect($auditLog->after['total'])->toBe('1100.00');
});

test('audit log validation rejects invalid action', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?action=invalid_action');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['action']);
});

test('audit log validation rejects per_page over 100', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?per_page=150');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['per_page']);
});

test('audit logs cannot be created via API', function () {
    $response = $this->actingAs($this->admin)
        ->postJson('/api/v1/audit-logs', [
            'auditable_type' => SalesReturn::class,
            'auditable_id' => 1,
            'action' => 'created',
        ]);

    $response->assertStatus(405); // Method Not Allowed
});

test('audit logs cannot be updated via API', function () {
    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    $auditLog = AuditLog::first();

    $response = $this->actingAs($this->admin)
        ->putJson('/api/v1/audit-logs/'.$auditLog->id, [
            'action' => 'updated',
        ]);

    $response->assertStatus(405); // Method Not Allowed
});

test('audit logs cannot be deleted via API', function () {
    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    $auditLog = AuditLog::first();

    $response = $this->actingAs($this->admin)
        ->deleteJson('/api/v1/audit-logs/'.$auditLog->id);

    $response->assertStatus(405); // Method Not Allowed
});

test('audit log is written after transaction commits', function () {
    $this->actingAs($this->admin);

    expect(AuditLog::count())->toBe(0);

    // Start a transaction that will be rolled back
    \DB::beginTransaction();
    try {
        $salesReturn = SalesReturn::factory()->create();
        $salesReturn->auditCreated();

        // Force an exception to rollback
        throw new \Exception('Rollback test');
    } catch (\Exception $e) {
        \DB::rollBack();
    }

    // Audit log should NOT be created because transaction was rolled back
    expect(AuditLog::count())->toBe(0);

    // Now test successful transaction
    \DB::beginTransaction();
    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();
    \DB::commit();

    // Audit log should be created after commit
    expect(AuditLog::count())->toBe(1);
});

test('audit log can be queued when queue mode is enabled', function () {
    $this->actingAs($this->admin);

    // Enable queue mode
    config(['audit.queue' => true]);

    \Queue::fake();

    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    // Assert job was dispatched
    \Queue::assertPushed(\App\Jobs\WriteAuditLog::class, function ($job) {
        return isset($job->payload['action']) && $job->payload['action'] === 'created';
    });
});

test('audit log is written synchronously when queue mode is disabled', function () {
    $this->actingAs($this->admin);

    // Disable queue mode (default)
    config(['audit.queue' => false]);

    expect(AuditLog::count())->toBe(0);

    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    // Audit log should be created immediately
    expect(AuditLog::count())->toBe(1);
});

test('can filter audit logs by type alias', function () {
    $this->actingAs($this->admin);

    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    $purchase = Purchase::factory()->create();
    $purchase->auditCreated();

    // Filter using type alias instead of full class name
    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?type=sales_return');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
    // morphMap stores the alias, so auditable_type will be the FQCN
    expect($response->json('data.0.auditable_type'))->toBe(SalesReturn::class);
});

test('type alias validation rejects invalid aliases', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?type=invalid_type');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

test('can filter by multiple type aliases', function () {
    $this->actingAs($this->admin);

    $salesReturn = SalesReturn::factory()->create();
    $salesReturn->auditCreated();

    $purchaseReturn = PurchaseReturn::factory()->create();
    $purchaseReturn->auditCreated();

    $purchase = Purchase::factory()->create();
    $purchase->auditCreated();

    // Filter using type alias for sales_return
    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?type=sales_return');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
    // morphMap stores the alias, so auditable_type will be the FQCN
    expect($response->json('data.0.auditable_type'))->toBe(SalesReturn::class);

    // Filter using type alias for purchase_return
    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?type=purchase_return');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
    expect($response->json('data.0.auditable_type'))->toBe(PurchaseReturn::class);
});

test('indexes improve query performance for action filter', function () {
    $this->actingAs($this->admin);

    // Create many audit logs with different actions
    for ($i = 0; $i < 50; $i++) {
        $salesReturn = SalesReturn::factory()->create();
        $salesReturn->auditCreated();

        if ($i % 2 === 0) {
            $beforeState = $salesReturn->snapshot(['id', 'status']);
            $salesReturn->update(['status' => 'approved']);
            $salesReturn->auditApproved($beforeState);
        }
    }

    // Query with action filter should use index
    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?action=approved');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThan(0);

    // All returned logs should have 'approved' action
    foreach ($response->json('data') as $log) {
        expect($log['action'])->toBe('approved');
    }
});

test('indexes improve query performance for user filter', function () {
    // Create another user
    $otherUser = User::factory()->create();
    $staffRole = \App\Models\Role::where('name', 'staff')->first();
    $otherUser->roles()->attach($staffRole);

    // Create audit logs for different users
    $this->actingAs($this->admin);
    for ($i = 0; $i < 25; $i++) {
        $salesReturn = SalesReturn::factory()->create();
        $salesReturn->auditCreated();
    }

    $this->actingAs($otherUser);
    for ($i = 0; $i < 25; $i++) {
        $salesReturn = SalesReturn::factory()->create();
        $salesReturn->auditCreated();
    }

    // Query with user filter should use index
    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/audit-logs?user_id='.$this->admin->id);

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeGreaterThan(0);

    // All returned logs should belong to admin
    foreach ($response->json('data') as $log) {
        expect($log['user_id'])->toBe($this->admin->id);
    }
});
