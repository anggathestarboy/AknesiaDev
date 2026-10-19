<?php

use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Forms\Livewire\PublicForm;
use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Models\FormSubmission;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

/**
 * Mirrors the contact form's blueprint in the admin panel (namespace
 * "forms.contact"), including the field handles submissions are stored
 * under.
 */
function makePublicContactBlueprint(): Blueprint
{
    return Blueprint::create([
        'namespace' => 'forms.contact',
        'handle' => 'contact',
        'data' => ['fields' => [
            ['handle' => 'name', 'display' => 'Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Your name'],
            ['handle' => 'email', 'display' => 'Email', 'type' => 'text', 'required' => true, 'validation' => 'email', 'placeholder' => 'you@example.com'],
            ['handle' => 'pesan', 'display' => 'Pesan', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Write your message...'],
        ]],
    ]);
}

function makePublicContactForm(array $settings = []): Form
{
    makePublicContactBlueprint();

    return Form::create(['handle' => 'contact', 'title' => 'Contact', 'settings' => $settings]);
}

test('the component renders the fields defined on the contact form blueprint', function () {
    makePublicContactForm();

    Livewire::test(PublicForm::class, ['handle' => 'contact'])
        ->assertSee('Name')
        ->assertSee('Email')
        ->assertSee('Pesan')
        ->assertSee('wire:model="data.name"', false)
        ->assertSee('wire:model="data.pesan"', false);
});

test('submitting the public form stores a submission for the contact form', function () {
    makePublicContactForm();

    Livewire::test(PublicForm::class, ['handle' => 'contact'])
        ->set('data.name', 'Andi Wijaya')
        ->set('data.email', 'andi@example.com')
        ->set('data.pesan', 'Mau tanya harga pembuatan website.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $submission = FormSubmission::sole();

    expect($submission->form)->toBe('contact');
    expect($submission->data)->toBe([
        'name' => 'Andi Wijaya',
        'email' => 'andi@example.com',
        'pesan' => 'Mau tanya harga pembuatan website.',
    ]);
});

test('submitting the public form without required blueprint fields fails validation and stores nothing', function () {
    makePublicContactForm();

    Livewire::test(PublicForm::class, ['handle' => 'contact'])
        ->call('submit')
        ->assertHasErrors(['data.name', 'data.email', 'data.pesan'])
        ->assertSee('The Email field is required.');

    expect(FormSubmission::count())->toBe(0);
});

test('the public form honours a blueprint field validation rule', function () {
    makePublicContactForm();

    Livewire::test(PublicForm::class, ['handle' => 'contact'])
        ->set('data.name', 'Andi Wijaya')
        ->set('data.email', 'bukan-email')
        ->set('data.pesan', 'Halo')
        ->call('submit')
        ->assertHasErrors(['data.email' => 'email']);

    expect(FormSubmission::count())->toBe(0);
});

test('a contact form configured not to store submissions confirms without persisting', function () {
    makePublicContactForm(['store_submissions' => false]);

    Livewire::test(PublicForm::class, ['handle' => 'contact'])
        ->set('data.name', 'Andi Wijaya')
        ->set('data.email', 'andi@example.com')
        ->set('data.pesan', 'Halo')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(FormSubmission::count())->toBe(0);
});

test('a contact form with a redirect url sends the visitor there after submitting', function () {
    makePublicContactForm(['redirect_url' => 'https://example.com/thanks']);

    Livewire::test(PublicForm::class, ['handle' => 'contact'])
        ->set('data.name', 'Andi Wijaya')
        ->set('data.email', 'andi@example.com')
        ->set('data.pesan', 'Halo')
        ->call('submit')
        ->assertRedirect('https://example.com/thanks');
});

test('a site template embeds the form through the forms livewire namespace tag', function () {
    makePublicContactForm();

    $html = Blade::render('<livewire:forms::public-form handle="contact" />');

    expect($html)->toContain('wire:model="data.name"')
        ->toContain('wire:model="data.pesan"');
});
