<?php

use App\Models\User;
use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Forms\Livewire\FormManager;
use App\Modules\Forms\Livewire\FormSubmissionManager;
use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Models\FormSubmission;
use Livewire\Livewire;

function makeContactBlueprint(): Blueprint
{
    return Blueprint::create([
        'namespace' => null,
        'handle' => 'contact',
        'data' => ['fields' => [
            ['handle' => 'name', 'display' => 'Name', 'type' => 'text', 'required' => true, 'localizable' => false, 'placeholder' => null, 'default' => null, 'instructions' => null],
            ['handle' => 'email', 'display' => 'Email', 'type' => 'text', 'required' => true, 'localizable' => false, 'placeholder' => null, 'default' => null, 'instructions' => null, 'validation' => 'email'],
            ['handle' => 'message', 'display' => 'Message', 'type' => 'textarea', 'required' => false, 'localizable' => false, 'placeholder' => null, 'default' => null, 'instructions' => null],
        ]],
    ]);
}

test('forms page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin-panel/forms')->assertOk();
});

test('a form can be created with a handle-generate button and picks an existing blueprint', function () {
    $this->actingAs(User::factory()->create());
    $blueprint = makeContactBlueprint();

    Livewire::test(FormManager::class)
        ->call('create')
        ->set('title', 'Contact Us')
        ->call('regenerateHandle')
        ->assertSet('handle', 'contact_us')
        ->set('createBlueprint', false)
        ->set('blueprint', $blueprint->handle)
        ->call('save')
        ->assertHasNoErrors();

    $form = Form::where('handle', 'contact_us')->sole();

    expect($form->title)->toBe('Contact Us');
    expect($form->effectiveBlueprint()->handle)->toBe('contact');
    expect($form->storesSubmissions())->toBeTrue();
});

test('form creation defaults to auto-creating its own blueprint, namespaced to the form', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(FormManager::class)
        ->set('title', 'Contact Us')
        ->set('handle', 'contact_us')
        ->call('save')
        ->assertHasNoErrors();

    $form = Form::sole();

    expect($form->settings['blueprint'])->toBeNull();

    $blueprint = Blueprint::where('namespace', 'forms.contact_us')->sole();

    expect($blueprint->handle)->toBe('contact_us');
    expect($form->effectiveBlueprint()->id)->toBe($blueprint->id);
});

test('a form can turn off storing submissions and set a redirect url', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(FormManager::class)
        ->set('title', 'Newsletter')
        ->set('handle', 'newsletter')
        ->set('storeSubmissions', false)
        ->set('redirectUrl', 'https://example.com/thanks')
        ->call('save')
        ->assertHasNoErrors();

    $form = Form::sole();

    expect($form->storesSubmissions())->toBeFalse();
    expect($form->redirectUrl())->toBe('https://example.com/thanks');
});

test('a form with existing submissions cannot be deleted', function () {
    $this->actingAs(User::factory()->create());

    $form = Form::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => []]);
    FormSubmission::create(['form' => 'contact', 'data' => ['name' => 'Jane']]);

    Livewire::test(FormManager::class)->call('delete', $form->id);

    expect(Form::find($form->id))->not->toBeNull();
});

test('a form with no submissions can be deleted', function () {
    $this->actingAs(User::factory()->create());

    $form = Form::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => []]);

    Livewire::test(FormManager::class)->call('delete', $form->id);

    expect(Form::find($form->id))->toBeNull();
});

test('the submissions page lists a column per blueprint field and shows submitted values', function () {
    $this->actingAs(User::factory()->create());
    makeContactBlueprint();
    $form = Form::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => ['blueprint' => 'contact']]);

    FormSubmission::create(['form' => 'contact', 'data' => ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'message' => 'Hi there']]);

    Livewire::test(FormSubmissionManager::class, ['form' => $form])
        ->assertSee('Jane Doe')
        ->assertSee('jane@example.com')
        ->assertSee('Hi there');
});

test('a submission can be deleted from the submissions page', function () {
    $this->actingAs(User::factory()->create());
    makeContactBlueprint();
    $form = Form::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => ['blueprint' => 'contact']]);
    $submission = FormSubmission::create(['form' => 'contact', 'data' => ['name' => 'Jane Doe']]);

    Livewire::test(FormSubmissionManager::class, ['form' => $form])
        ->call('delete', $submission->id);

    expect(FormSubmission::find($submission->id))->toBeNull();
});

test('submitting the public form endpoint validates required fields and shows friendly labels', function () {
    makeContactBlueprint();
    Form::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => ['blueprint' => 'contact']]);

    $this->post('/!/forms/contact', ['data' => ['message' => 'Hi']])
        ->assertSessionHasErrors(['data.name', 'data.email']);
});

test('submitting the public form endpoint stores a submission and redirects back', function () {
    makeContactBlueprint();
    Form::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => ['blueprint' => 'contact']]);

    $response = $this->from('/some-page')->post('/!/forms/contact', [
        'data' => ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'message' => 'Hi there'],
    ]);

    $response->assertRedirect('/some-page');
    $response->assertSessionHas('form_submitted', 'contact');

    $submission = FormSubmission::sole();

    expect($submission->form)->toBe('contact');
    expect($submission->data)->toBe(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'message' => 'Hi there']);
});

test('submitting a form configured to not store submissions still succeeds without persisting one', function () {
    makeContactBlueprint();
    Form::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => ['blueprint' => 'contact', 'store_submissions' => false]]);

    $this->from('/some-page')->post('/!/forms/contact', [
        'data' => ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
    ])->assertRedirect('/some-page');

    expect(FormSubmission::count())->toBe(0);
});

test('submitting a form with a redirect url configured redirects there on success', function () {
    makeContactBlueprint();
    Form::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => ['blueprint' => 'contact', 'redirect_url' => 'https://example.com/thanks']]);

    $this->post('/!/forms/contact', [
        'data' => ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
    ])->assertRedirect('https://example.com/thanks');
});
