<?php

namespace Afterburner\Communications\Tests;

use Afterburner\Communications\Providers\CommunicationsServiceProvider;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'afterburner-communications.enabled' => true,
            'afterburner-communications.discussions.enabled' => true,
            'afterburner-communications.communication_log.enabled' => true,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            CommunicationsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('auth.guards.web.provider', 'users');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function seedPermissions(): void
    {
        $now = now();
        $permissions = [
            ['name' => 'Manage Discussions', 'slug' => 'manage_discussions', 'description' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'View Communication Log', 'slug' => 'view_communication_log', 'description' => null, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert($permission);
        }
    }

    protected function createRoleWithPermissions(string $slug, array $permissionSlugs): int
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => ucfirst(str_replace('_', ' ', $slug)),
            'slug' => $slug,
            'hierarchy' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($permissionSlugs as $permissionSlug) {
            $permissionId = DB::table('permissions')->where('slug', $permissionSlug)->value('id');
            DB::table('role_permission')->insert([
                'role_slug' => $slug,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $roleId;
    }

    protected function createTeamWithUser(array $permissions = ['manage_discussions']): array
    {
        $this->seedPermissions();
        $roleId = $this->createRoleWithPermissions('member', $permissions);

        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $team = Team::query()->create([
            'name' => 'Test Team',
            'user_id' => $user->id,
        ]);

        $team->users()->attach($user);
        $user->update(['current_team_id' => $team->id]);

        DB::table('user_role')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'team_id' => $team->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $team];
    }

    protected function createAdditionalUser(Team $team, array $permissions = [], string $email = 'member@example.com'): User
    {
        $roleId = $this->createRoleWithPermissions('member_'.$email, $permissions);

        $user = User::query()->create([
            'name' => 'Member User',
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $team->users()->attach($user);
        $user->update(['current_team_id' => $team->id]);

        DB::table('user_role')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'team_id' => $team->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }
}
