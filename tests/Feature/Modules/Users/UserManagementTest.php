<?php

use App\Models\User;
use App\Modules\Collections\Models\Collection;
use App\Modules\Globals\Models\GlobalSet;
use App\Modules\Navigation\Models\Navigation;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Livewire\RoleForm;
use App\Modules\Users\Livewire\RoleManager;
use App\Modules\Users\Livewire\UserForm;
use App\Modules\Users\Livewire\UserGroupManager;
use App\Modules\Users\Livewire\UserManager;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\UserGroup;
use App\Modules\Users\Services\PermissionRegistry;
use Livewire\Livewire;

test('the users page lists existing users', function () {
    $this->actingAs(User::factory()->create());
    $jane = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    Livewire::test(UserManager::class)->assertSee('Jane Doe')->assertSee('jane@example.com');
});

test('creating a user assigns roles and groups', function () {
    $this->actingAs(User::factory()->create());
    $role = Role::create(['handle' => 'editor', 'title' => 'Editor', 'permissions' => ['access cp']]);
    $group = UserGroup::create(['handle' => 'staff', 'title' => 'Staff']);

    Livewire::test(UserForm::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('roleHandles', [$role->handle])
        ->set('groupHandles', [$group->handle])
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'jane@example.com')->sole();
    expect($user->roles->pluck('handle')->all())->toBe([$role->handle]);
    expect($user->userGroups->pluck('handle')->all())->toBe([$group->handle]);
});

test('a user cannot be created without a password', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(UserForm::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->call('save')
        ->assertHasErrors(['password']);
});

test('editing a user without a password keeps the existing one', function () {
    $this->actingAs(User::factory()->create());
    $user = User::factory()->create(['password' => bcrypt('original-password')]);
    $originalHash = $user->password;

    Livewire::test(UserForm::class, ['user' => $user])
        ->set('name', 'Updated Name')
        ->set('email', $user->email)
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->password)->toBe($originalHash);
    expect($user->fresh()->name)->toBe('Updated Name');
});

test('a user cannot delete their own account', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(UserManager::class)->call('delete', $user->id);

    expect(User::find($user->id))->not->toBeNull();
});

test('deleting another user removes them', function () {
    $this->actingAs(User::factory()->create());
    $target = User::factory()->create();

    Livewire::test(UserManager::class)->call('delete', $target->id);

    expect(User::find($target->id))->toBeNull();
});

test('creating a role persists its selected permissions', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(RoleForm::class)
        ->set('title', 'Editor')
        ->set('handle', 'editor')
        ->set('permissions', ['access cp', 'configure collections'])
        ->call('save')
        ->assertHasNoErrors();

    $role = Role::where('handle', 'editor')->sole();
    expect($role->permissions)->toBe(['access cp', 'configure collections']);
});

test('a role handle regenerates from its title', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(RoleForm::class)
        ->set('title', 'Content Editor')
        ->call('regenerateHandle')
        ->assertSet('handle', 'content_editor');
});

test('the roles page lists roles with their user count', function () {
    $this->actingAs(User::factory()->create());
    $role = Role::create(['handle' => 'editor', 'title' => 'Editor', 'permissions' => []]);
    User::factory()->create()->roles()->attach($role->id);

    Livewire::test(RoleManager::class)->assertSee('Editor')->assertSee('1');
});

test('deleting a role removes it', function () {
    $this->actingAs(User::factory()->create());
    $role = Role::create(['handle' => 'editor', 'title' => 'Editor', 'permissions' => []]);

    Livewire::test(RoleManager::class)->call('delete', $role->id);

    expect(Role::find($role->id))->toBeNull();
});

test('creating a user group assigns roles to it', function () {
    $this->actingAs(User::factory()->create());
    $role = Role::create(['handle' => 'editor', 'title' => 'Editor', 'permissions' => ['access cp']]);

    Livewire::test(UserGroupManager::class)
        ->call('create')
        ->set('title', 'Staff')
        ->set('handle', 'staff')
        ->set('roleHandles', [$role->handle])
        ->call('save')
        ->assertHasNoErrors();

    $group = UserGroup::where('handle', 'staff')->sole();
    expect($group->roles->pluck('handle')->all())->toBe([$role->handle]);
});

test('a user inherits permissions from a direct role and from a user group', function () {
    $directRole = Role::create(['handle' => 'direct', 'title' => 'Direct', 'permissions' => ['access cp']]);
    $groupRole = Role::create(['handle' => 'group-role', 'title' => 'Group Role', 'permissions' => ['configure collections']]);
    $group = UserGroup::create(['handle' => 'staff', 'title' => 'Staff']);
    $group->roles()->attach($groupRole->id);

    $user = User::factory()->create();
    $user->roles()->attach($directRole->id);
    $user->userGroups()->attach($group->id);

    expect($user->hasPermission('access cp'))->toBeTrue();
    expect($user->hasPermission('configure collections'))->toBeTrue();
    expect($user->hasPermission('configure users'))->toBeFalse();
});

test('a role with the super permission grants every permission', function () {
    $role = Role::create(['handle' => 'super-admin', 'title' => 'Super Admin', 'permissions' => ['super']]);
    $user = User::factory()->create();
    $user->roles()->attach($role->id);

    expect($user->isSuper())->toBeTrue();
    expect($user->hasPermission('configure users'))->toBeTrue();
    expect($user->hasPermission('anything at all'))->toBeTrue();
});

test('the permission registry generates checkboxes for existing collections', function () {
    Collection::create(['handle' => 'blog', 'title' => 'Blog']);

    $groups = app(PermissionRegistry::class)->groups();
    $collectionsGroup = collect($groups)->firstWhere('group', 'Collections');

    expect($collectionsGroup)->not->toBeNull();
    $flatValues = collect(app(PermissionRegistry::class)->flatten($collectionsGroup['permissions']))->pluck('value')->all();
    expect($flatValues)->toContain(
        'configure collections', 'view blog entries', 'edit blog entries', 'create blog entries', 'delete blog entries', 'publish blog entries',
    );

    // "Edit entries" etc. are nested under their collection's "view" entry,
    // not siblings of it — this is what the role form indents under the
    // parent checkbox to make each collection's block easy to tell apart.
    $viewBlog = collect($collectionsGroup['permissions'])->firstWhere('value', 'view blog entries');
    expect(collect($viewBlog['children'])->pluck('value')->all())->toContain('edit blog entries', 'create blog entries', 'delete blog entries', 'publish blog entries');
});

test('the permission registry only offers reorder permissions for orderable collections', function () {
    Collection::create(['handle' => 'pages', 'title' => 'Pages', 'settings' => ['orderable' => true]]);
    Collection::create(['handle' => 'blog', 'title' => 'Blog']);

    $values = app(PermissionRegistry::class)->values();

    expect($values)->toContain('reorder pages entries');
    expect($values)->not->toContain('reorder blog entries');
});

test('the permission registry generates a site access checkbox per configured site', function () {
    Site::create(['handle' => 'indonesia', 'name' => 'Indonesia', 'url' => 'https://example.id', 'locale' => 'id_ID', 'lang' => 'id', 'attributes' => [], 'order' => 1]);

    $groups = app(PermissionRegistry::class)->groups();
    $sitesGroup = collect($groups)->firstWhere('group', 'Sites');

    expect(collect($sitesGroup['permissions'])->pluck('value')->all())->toContain('access indonesia site');
});

test('the permission registry generates its own checkboxes per global set', function () {
    GlobalSet::create(['handle' => 'header', 'title' => 'Header', 'settings' => []]);
    GlobalSet::create(['handle' => 'footer', 'title' => 'Footer', 'settings' => []]);

    $globalsGroup = collect(app(PermissionRegistry::class)->groups())->firstWhere('group', 'Globals');
    $registry = app(PermissionRegistry::class);
    $values = collect($registry->flatten($globalsGroup['permissions']))->pluck('value')->all();

    expect($values)->toContain(
        'view header globals', 'edit header globals', 'delete header globals',
        'view footer globals', 'edit footer globals', 'delete footer globals',
    );

    $viewHeader = collect($globalsGroup['permissions'])->firstWhere('value', 'view header globals');
    expect(collect($viewHeader['children'])->pluck('value')->all())->toBe(['edit header globals', 'delete header globals']);
});

test('the permission registry generates its own checkboxes per navigation menu', function () {
    Navigation::create(['handle' => 'main', 'title' => 'Main Menu']);

    $navigationGroup = collect(app(PermissionRegistry::class)->groups())->firstWhere('group', 'Navigation');
    $values = collect(app(PermissionRegistry::class)->flatten($navigationGroup['permissions']))->pluck('value')->all();

    expect($values)->toContain('view main navigation', 'edit main navigation', 'delete main navigation');
});
