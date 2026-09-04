<?php

use App\Models\User;
use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Globals\Livewire\GlobalSetForm;
use App\Modules\Globals\Livewire\GlobalSetManager;
use App\Modules\Globals\Models\GlobalSet;
use App\Modules\Globals\Models\GlobalSetVariable;
use App\Modules\Sites\Models\Site;
use Livewire\Livewire;

function makeSite(string $handle, int $order = 0): Site
{
    return Site::create([
        'handle' => $handle,
        'name' => ucfirst($handle),
        'url' => "https://{$handle}.example.com",
        'locale' => $handle,
        'lang' => $handle,
        'attributes' => [],
        'order' => $order,
    ]);
}

test('globals page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin-panel/globals')->assertOk();
});

test('global set can be created with an existing blueprint and gets an empty values row', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'site_settings', 'data' => ['fields' => [
        ['handle' => 'site_name', 'display' => 'Site name', 'type' => 'text', 'required' => false],
    ]]]);

    Livewire::test(GlobalSetManager::class)
        ->set('title', 'Site Settings')
        ->set('handle', 'site_settings')
        ->set('createBlueprint', false)
        ->set('blueprint', 'site_settings')
        ->call('save')
        ->assertHasNoErrors();

    $globalSet = GlobalSet::sole();

    expect($globalSet->title)->toBe('Site Settings');
    expect($globalSet->settings['blueprint'])->toBe('site_settings');
    expect(GlobalSetVariable::where('handle', 'site_settings')->exists())->toBeTrue();
});

test('global set creation defaults to auto-creating its own blueprint, namespaced to the global set', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(GlobalSetManager::class)
        ->set('title', 'Site Settings')
        ->set('handle', 'site_settings')
        ->call('save')
        ->assertHasNoErrors();

    $globalSet = GlobalSet::sole();

    expect($globalSet->settings['blueprint'])->toBeNull();

    $blueprint = Blueprint::where('namespace', 'globals.site_settings')->sole();

    expect($blueprint->handle)->toBe('site_settings');
    expect($blueprint->data['fields'])->toBe([]);
    expect($globalSet->effectiveBlueprint()->id)->toBe($blueprint->id);
});

test('global set handle can be regenerated from the title', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(GlobalSetManager::class)
        ->set('title', 'Site Settings')
        ->call('regenerateHandle')
        ->assertSet('handle', 'site_settings');
});

test('global set handle rejects spaces and dashes', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(GlobalSetManager::class)
        ->set('title', 'Site Settings')
        ->set('handle', 'site settings')
        ->call('save')
        ->assertHasErrors(['handle']);

    Livewire::test(GlobalSetManager::class)
        ->set('title', 'Site Settings')
        ->set('handle', 'site-settings')
        ->call('save')
        ->assertHasErrors(['handle']);
});

test('global set handle must be unique', function () {
    $this->actingAs(User::factory()->create());

    GlobalSet::create(['title' => 'Site Settings', 'handle' => 'site_settings', 'settings' => []]);

    Livewire::test(GlobalSetManager::class)
        ->set('title', 'Other')
        ->set('handle', 'site_settings')
        ->call('save')
        ->assertHasErrors(['handle']);
});

test('global set values can be filled in and saved through its blueprint fields', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'site_settings', 'data' => ['fields' => [
        ['handle' => 'site_name', 'display' => 'Site name', 'type' => 'text', 'required' => true, 'placeholder' => null, 'default' => null, 'instructions' => null],
    ]]]);

    $globalSet = GlobalSet::create([
        'title' => 'Site Settings',
        'handle' => 'site_settings',
        'settings' => ['blueprint' => 'site_settings'],
    ]);

    Livewire::test(GlobalSetForm::class, ['globalSet' => $globalSet])
        ->set('data.site_name', 'Acme Inc')
        ->call('save')
        ->assertHasNoErrors();

    $variable = GlobalSetVariable::where('handle', 'site_settings')->sole();

    expect($variable->data)->toBe(['site_name' => 'Acme Inc']);
});

test('global set values require blueprint required fields', function () {
    $this->actingAs(User::factory()->create());

    Blueprint::create(['namespace' => null, 'handle' => 'site_settings', 'data' => ['fields' => [
        ['handle' => 'site_name', 'display' => 'Site name', 'type' => 'text', 'required' => true, 'placeholder' => null, 'default' => null, 'instructions' => null],
    ]]]);

    $globalSet = GlobalSet::create([
        'title' => 'Site Settings',
        'handle' => 'site_settings',
        'settings' => ['blueprint' => 'site_settings'],
    ]);

    Livewire::test(GlobalSetForm::class, ['globalSet' => $globalSet])
        ->set('data.site_name', '')
        ->call('save')
        ->assertHasErrors(['data.site_name']);
});

test('creating a global set defaults to checking the first configured site', function () {
    $this->actingAs(User::factory()->create());
    makeSite('indonesia', 0);
    makeSite('english', 1);

    Livewire::test(GlobalSetManager::class)
        ->call('create')
        ->assertSet('sites', ['indonesia']);
});

test('global set with no sites checked stores values under a null locale, no switcher shown', function () {
    $this->actingAs(User::factory()->create());

    $globalSet = GlobalSet::create(['title' => 'Footer', 'handle' => 'footer', 'settings' => ['sites' => []]]);
    GlobalSetVariable::create(['handle' => 'footer', 'locale' => null, 'data' => []]);

    $component = Livewire::test(GlobalSetForm::class, ['globalSet' => $globalSet]);

    expect($component->get('activeSite'))->toBe('');
    $component->assertSee('This blueprint has no fields yet.');
});

test('global set with exactly one site checked has no switcher but still saves under that locale', function () {
    $this->actingAs(User::factory()->create());
    makeSite('indonesia');

    Blueprint::create(['namespace' => null, 'handle' => 'footer', 'data' => ['fields' => [
        ['handle' => 'copyright', 'display' => 'Copyright', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'instructions' => null],
    ]]]);

    $globalSet = GlobalSet::create(['title' => 'Footer', 'handle' => 'footer', 'settings' => ['blueprint' => 'footer', 'sites' => ['indonesia']]]);

    Livewire::test(GlobalSetForm::class, ['globalSet' => $globalSet])
        ->assertSet('activeSite', 'indonesia')
        ->set('data.copyright', '© Acme')
        ->call('save')
        ->assertHasNoErrors();

    $variable = GlobalSetVariable::where('handle', 'footer')->where('locale', 'indonesia')->sole();
    expect($variable->data)->toBe(['copyright' => '© Acme']);
});

test('global set with two or more sites checked gets a language switcher keyed by ?site=', function () {
    $this->actingAs(User::factory()->create());
    makeSite('indonesia', 0);
    makeSite('english', 1);

    Blueprint::create(['namespace' => null, 'handle' => 'footer', 'data' => ['fields' => [
        ['handle' => 'copyright', 'display' => 'Copyright', 'type' => 'text', 'required' => false, 'localizable' => true, 'placeholder' => null, 'default' => null, 'instructions' => null],
    ]]]);

    $globalSet = GlobalSet::create(['title' => 'Footer', 'handle' => 'footer', 'settings' => ['blueprint' => 'footer', 'sites' => ['indonesia', 'english']]]);

    // No ?site= yet: defaults to the first configured site.
    Livewire::test(GlobalSetForm::class, ['globalSet' => $globalSet])
        ->assertSet('activeSite', 'indonesia')
        ->set('data.copyright', 'Indonesian copyright')
        ->call('save')
        ->assertHasNoErrors();

    // Switching to ?site=english edits a separate value row.
    Livewire::test(GlobalSetForm::class, ['globalSet' => $globalSet, 'activeSite' => 'english'])
        ->assertSet('activeSite', 'english')
        ->set('data.copyright', 'English copyright')
        ->call('save')
        ->assertHasNoErrors();

    expect(GlobalSetVariable::where('handle', 'footer')->where('locale', 'indonesia')->sole()->data)
        ->toBe(['copyright' => 'Indonesian copyright']);
    expect(GlobalSetVariable::where('handle', 'footer')->where('locale', 'english')->sole()->data)
        ->toBe(['copyright' => 'English copyright']);

    $component = Livewire::test(GlobalSetForm::class, ['globalSet' => $globalSet]);
    expect($component->get('siteLinks'))->toHaveCount(2);
});

test('non-localizable fields share one value from the default site across all languages', function () {
    $this->actingAs(User::factory()->create());
    makeSite('indonesia', 0);
    makeSite('english', 1);

    Blueprint::create(['namespace' => null, 'handle' => 'footer', 'data' => ['fields' => [
        ['handle' => 'company_name', 'display' => 'Company name', 'type' => 'text', 'required' => false, 'localizable' => false, 'placeholder' => null, 'default' => null, 'instructions' => null],
        ['handle' => 'tagline', 'display' => 'Tagline', 'type' => 'text', 'required' => false, 'localizable' => true, 'placeholder' => null, 'default' => null, 'instructions' => null],
    ]]]);

    $globalSet = GlobalSet::create(['title' => 'Footer', 'handle' => 'footer', 'settings' => ['blueprint' => 'footer', 'sites' => ['indonesia', 'english']]]);

    Livewire::test(GlobalSetForm::class, ['globalSet' => $globalSet])
        ->assertSet('activeSite', 'indonesia')
        ->set('data.company_name', 'Acme Inc')
        ->set('data.tagline', 'Solusi terbaik')
        ->call('save')
        ->assertHasNoErrors();

    // English editor shows Acme's shared value, but its own tagline.
    Livewire::test(GlobalSetForm::class, ['globalSet' => $globalSet, 'activeSite' => 'english'])
        ->assertSet('data.company_name', 'Acme Inc')
        ->assertSet('data.tagline', '')
        ->set('data.tagline', 'Best solution')
        ->set('data.company_name', 'Should be ignored')
        ->call('save')
        ->assertHasNoErrors();

    $indonesian = GlobalSetVariable::where('handle', 'footer')->where('locale', 'indonesia')->sole();
    $english = GlobalSetVariable::where('handle', 'footer')->where('locale', 'english')->sole();

    expect($indonesian->data)->toBe(['company_name' => 'Acme Inc', 'tagline' => 'Solusi terbaik']);
    expect($english->data)->toBe(['company_name' => 'Acme Inc', 'tagline' => 'Best solution']);
});

test('global set can be deleted along with its values', function () {
    $this->actingAs(User::factory()->create());

    $globalSet = GlobalSet::create(['title' => 'Site Settings', 'handle' => 'site_settings', 'settings' => []]);
    GlobalSetVariable::create(['handle' => 'site_settings', 'data' => []]);

    Livewire::test(GlobalSetManager::class)->call('delete', $globalSet->id);

    expect(GlobalSet::find($globalSet->id))->toBeNull();
    expect(GlobalSetVariable::where('handle', 'site_settings')->exists())->toBeFalse();
});

test('saving a global set with multiple sites checked creates one values row per site', function () {
    $this->actingAs(User::factory()->create());
    makeSite('indonesia', 0);
    makeSite('english', 1);

    Livewire::test(GlobalSetManager::class)
        ->set('title', 'Footer')
        ->set('handle', 'footer')
        ->set('sites', ['indonesia', 'english'])
        ->call('save')
        ->assertHasNoErrors();

    $globalSet = GlobalSet::sole();

    expect($globalSet->settings['sites'])->toBe(['indonesia', 'english']);
    expect(GlobalSetVariable::where('handle', 'footer')->pluck('locale')->sort()->values()->all())
        ->toBe(['english', 'indonesia']);
});

test('global set sites must reference existing site handles', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(GlobalSetManager::class)
        ->set('title', 'Footer')
        ->set('handle', 'footer')
        ->set('sites', ['does-not-exist'])
        ->call('save')
        ->assertHasErrors(['sites.0']);
});
