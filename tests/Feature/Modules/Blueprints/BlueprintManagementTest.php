<?php

use App\Models\User;
use App\Modules\Blueprints\Livewire\BlueprintManager;
use App\Modules\Blueprints\Livewire\FieldsetManager;
use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Blueprints\Models\Fieldset;
use App\Modules\Collections\Models\Collection;
use App\Modules\Media\Models\AssetContainer;
use Livewire\Livewire;

test('blueprints page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin-panel/blueprints')->assertOk();
});

test('namespace options are derived from existing collections', function () {
    $this->actingAs(User::factory()->create());
    Collection::create(['title' => 'Blog', 'handle' => 'blog', 'settings' => []]);
    Collection::create(['title' => 'Pages', 'handle' => 'pages', 'settings' => []]);

    Livewire::test(BlueprintManager::class)
        ->assertSee('collections.blog')
        ->assertSee('collections.pages');
});

test('a message is shown when no collections exist yet for the namespace picker', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->assertSee('No collections yet');
});

test('blueprint namespace must match an existing collection', function () {
    $this->actingAs(User::factory()->create());
    Collection::create(['title' => 'Blog', 'handle' => 'blog', 'settings' => []]);

    Livewire::test(BlueprintManager::class)
        ->set('namespace', 'collections.does-not-exist')
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'title', 'display' => '', 'type' => 'text', 'required' => false, 'options' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['namespace']);
});

test('blueprint can be created with no namespace', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'seo')
        ->set('fields', [
            ['handle' => 'headline', 'display' => '', 'type' => 'text', 'required' => false, 'options' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(Blueprint::sole()->namespace)->toBeNull();
});

test('blueprint can be created with fields via the field builder', function () {
    $this->actingAs(User::factory()->create());
    Collection::create(['title' => 'Blog', 'handle' => 'blog', 'settings' => []]);

    Livewire::test(BlueprintManager::class)
        ->set('namespace', 'collections.blog')
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'headline', 'display' => 'Headline', 'type' => 'text', 'required' => true, 'options' => '', 'placeholder' => 'Enter a headline', 'default' => '', 'validation' => 'max:255', 'instructions' => 'Shown in listings.', 'sometimes' => false, 'condition_mode' => 'always', 'condition_field' => '', 'condition_operator' => 'equals', 'condition_value' => ''],
            ['handle' => 'status', 'display' => '', 'type' => 'select', 'required' => false, 'options' => 'draft, published, archived', 'placeholder' => '', 'default' => 'draft', 'validation' => '', 'instructions' => '', 'sometimes' => false, 'condition_mode' => 'always', 'condition_field' => '', 'condition_operator' => 'equals', 'condition_value' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $blueprint = Blueprint::sole();

    expect($blueprint->namespace)->toBe('collections.blog');
    expect($blueprint->handle)->toBe('post');
    expect($blueprint->data)->toBe([
        'fields' => [
            [
                'handle' => 'headline', 'display' => 'Headline', 'type' => 'text', 'required' => true,
                'placeholder' => 'Enter a headline', 'default' => null, 'validation' => 'max:255', 'instructions' => 'Shown in listings.',
                'validate_sometimes' => false, 'conditions' => null,
            ],
            [
                'handle' => 'status', 'display' => 'Status', 'type' => 'select', 'required' => false,
                'placeholder' => null, 'default' => 'draft', 'validation' => null, 'instructions' => null,
                'validate_sometimes' => false, 'conditions' => null,
                'options' => ['draft', 'published', 'archived'],
            ],
        ],
    ]);
});

test('blueprint field can show when another field matches a value', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'type', 'display' => '', 'type' => 'select', 'required' => false, 'options' => 'article, video', 'placeholder' => '', 'default' => '', 'validation' => '', 'instructions' => '', 'sometimes' => true, 'condition_mode' => 'always', 'condition_field' => '', 'condition_operator' => 'equals', 'condition_value' => ''],
            ['handle' => 'video_url', 'display' => '', 'type' => 'text', 'required' => false, 'options' => '', 'placeholder' => '', 'default' => '', 'validation' => 'url', 'instructions' => '', 'sometimes' => true, 'condition_mode' => 'show', 'condition_field' => 'type', 'condition_operator' => 'equals', 'condition_value' => 'video'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $blueprint = Blueprint::sole();

    expect($blueprint->data['fields'][1])->toBe([
        'handle' => 'video_url', 'display' => 'Video Url', 'type' => 'text', 'required' => false,
        'placeholder' => null, 'default' => null, 'validation' => 'url', 'instructions' => null,
        'validate_sometimes' => true,
        'conditions' => ['mode' => 'show', 'field' => 'type', 'operator' => 'equals', 'value' => 'video'],
    ]);
});

test('blueprint field can hide when another field matches a value', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'status', 'display' => '', 'type' => 'select', 'required' => false, 'options' => 'draft, published', 'placeholder' => '', 'default' => '', 'validation' => '', 'instructions' => '', 'sometimes' => false, 'condition_mode' => 'always', 'condition_field' => '', 'condition_operator' => 'equals', 'condition_value' => ''],
            ['handle' => 'draft_note', 'display' => '', 'type' => 'text', 'required' => false, 'options' => '', 'placeholder' => '', 'default' => '', 'validation' => '', 'instructions' => '', 'sometimes' => false, 'condition_mode' => 'hide', 'condition_field' => 'status', 'condition_operator' => 'equals', 'condition_value' => 'published'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $blueprint = Blueprint::sole();

    expect($blueprint->data['fields'][1]['conditions'])->toBe([
        'mode' => 'hide', 'field' => 'status', 'operator' => 'equals', 'value' => 'published',
    ]);
});

test('blueprint condition requires a target field to be selected', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'headline', 'display' => '', 'type' => 'text', 'required' => false, 'options' => '', 'placeholder' => '', 'default' => '', 'validation' => '', 'instructions' => '', 'sometimes' => false, 'condition_mode' => 'show', 'condition_field' => '', 'condition_operator' => 'equals', 'condition_value' => 'x'],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.condition_field']);
});

test('blueprint condition cannot reference the field itself', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'headline', 'display' => '', 'type' => 'text', 'required' => false, 'options' => '', 'placeholder' => '', 'default' => '', 'validation' => '', 'instructions' => '', 'sometimes' => false, 'condition_mode' => 'show', 'condition_field' => 'headline', 'condition_operator' => 'equals', 'condition_value' => 'x'],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.condition_field']);
});

test('blueprint condition requires a value to compare against', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'headline', 'display' => '', 'type' => 'text', 'required' => false, 'options' => '', 'placeholder' => '', 'default' => '', 'validation' => '', 'instructions' => '', 'sometimes' => false, 'condition_mode' => 'always', 'condition_field' => '', 'condition_operator' => 'equals', 'condition_value' => ''],
            ['handle' => 'subtitle', 'display' => '', 'type' => 'text', 'required' => false, 'options' => '', 'placeholder' => '', 'default' => '', 'validation' => '', 'instructions' => '', 'sometimes' => false, 'condition_mode' => 'show', 'condition_field' => 'headline', 'condition_operator' => 'equals', 'condition_value' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['fields.1.condition_value']);
});

test('condition inputs only appear once show or hide is selected, and reset when set back to always', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->call('create')
        ->call('addField')
        ->set('fields.0.handle', 'title')
        ->set('fields.1.condition_mode', 'show')
        ->set('fields.1.condition_field', 'title')
        ->set('fields.1.condition_value', 'x')
        ->assertSet('fields.1.condition_field', 'title')
        ->set('fields.1.condition_mode', 'always')
        ->assertSet('fields.1.condition_field', '')
        ->assertSet('fields.1.condition_value', '');
});

test('blueprint rejects an invalid Laravel validation rule string', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'headline', 'display' => '', 'type' => 'text', 'required' => false, 'options' => '', 'placeholder' => '', 'default' => '', 'validation' => 'not_a_real_rule', 'instructions' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.validation']);
});

test('blueprint rejects a validation rule string with disallowed characters', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'title', 'display' => '', 'type' => 'text', 'required' => false, 'options' => '', 'placeholder' => '', 'default' => '', 'validation' => 'max:255; DROP TABLE users;', 'instructions' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.validation']);
});

test('blueprint accepts a valid date default value', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'published_at', 'display' => '', 'type' => 'date', 'required' => false, 'options' => '', 'placeholder' => '', 'default' => '2026-01-15', 'validation' => '', 'instructions' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $blueprint = Blueprint::sole();

    expect($blueprint->data['fields'][0]['default'])->toBe('2026-01-15');
    expect($blueprint->data['fields'][0]['placeholder'])->toBeNull();
});

test('blueprint rejects a malformed date default value', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'published_at', 'display' => '', 'type' => 'date', 'required' => false, 'options' => '', 'placeholder' => '', 'default' => 'not-a-date', 'validation' => '', 'instructions' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.default']);
});

test('changing a field type to date clears its placeholder', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->call('create')
        ->set('fields.0.placeholder', 'some placeholder')
        ->set('fields.0.type', 'date')
        ->assertSet('fields.0.placeholder', '');
});

test('blueprint handle must be unique within its namespace', function () {
    $this->actingAs(User::factory()->create());
    Collection::create(['title' => 'Blog', 'handle' => 'blog', 'settings' => []]);

    Blueprint::create(['namespace' => 'collections.blog', 'handle' => 'post', 'data' => ['fields' => []]]);

    Livewire::test(BlueprintManager::class)
        ->set('namespace', 'collections.blog')
        ->set('handle', 'post')
        ->set('fields', [['handle' => 'title', 'display' => '', 'type' => 'text', 'required' => false, 'options' => '']])
        ->call('save')
        ->assertHasErrors(['handle']);
});

test('blueprint requires at least one field', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [])
        ->call('save')
        ->assertHasErrors(['fields']);
});

test('blueprint field handles must be unique within the blueprint', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'headline', 'display' => '', 'type' => 'text', 'required' => false, 'options' => ''],
            ['handle' => 'headline', 'display' => '', 'type' => 'text', 'required' => false, 'options' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['fields']);
});

test('blueprint field handle must match the allowed format', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'Not Valid!', 'display' => '', 'type' => 'text', 'required' => false, 'options' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.handle']);
});

test('blueprint field handle cannot be "title" — it is a reserved default field', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'title', 'display' => '', 'type' => 'text', 'required' => false, 'options' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.handle']);
});

test('replicator field can be created with sub-fields', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            [
                'handle' => 'gallery', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => '',
                'replicator_fields' => [
                    ['handle' => 'caption', 'display' => '', 'type' => 'text', 'required' => true],
                    ['handle' => 'featured', 'display' => '', 'type' => 'toggle', 'required' => false],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $blueprint = Blueprint::sole();
    $field = $blueprint->data['fields'][0];

    expect($field['type'])->toBe('replicator');
    expect($field['replicator_fields'])->toHaveCount(2);
    expect($field['replicator_fields'][0])->toMatchArray(['handle' => 'caption', 'type' => 'text', 'required' => true]);
});

test('replicator field requires at least one sub-field', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'gallery', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.replicator_fields']);
});

test('replicator sub-field handle cannot be "title"', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            [
                'handle' => 'gallery', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => '',
                'replicator_fields' => [
                    ['handle' => 'title', 'display' => '', 'type' => 'text', 'required' => false],
                ],
            ],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.replicator_fields.0.handle']);
});

test('replicator sub-field default value, placeholder and instructions round-trip through the blueprint builder', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            [
                'handle' => 'gallery', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => '',
                'replicator_fields' => [
                    [
                        'handle' => 'caption', 'display' => '', 'type' => 'text', 'required' => false,
                        'placeholder' => 'Enter a caption', 'default' => 'Untitled', 'validation' => 'max:100', 'instructions' => 'Shown under the image',
                    ],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $sub = Blueprint::sole()->data['fields'][0]['replicator_fields'][0];

    expect($sub)->toMatchArray([
        'placeholder' => 'Enter a caption',
        'default' => 'Untitled',
        'validation' => 'max:100',
        'instructions' => 'Shown under the image',
    ]);
});

test('replicator sub-field with an invalid validation rule string fails', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            [
                'handle' => 'gallery', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => '',
                'replicator_fields' => [
                    ['handle' => 'caption', 'display' => '', 'type' => 'text', 'required' => false, 'validation' => 'not_a_real_rule'],
                ],
            ],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.replicator_fields.0.validation']);
});

test('replicator field can contain another replicator, nested to any depth', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            [
                'handle' => 'sections', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => '',
                'replicator_fields' => [
                    [
                        'handle' => 'items', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => '',
                        'replicator_fields' => [
                            ['handle' => 'label', 'display' => '', 'type' => 'text', 'required' => true],
                        ],
                    ],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $sections = Blueprint::sole()->data['fields'][0];

    expect($sections['type'])->toBe('replicator');
    expect($sections['replicator_fields'][0]['type'])->toBe('replicator');
    expect($sections['replicator_fields'][0]['replicator_fields'][0])->toMatchArray([
        'handle' => 'label', 'type' => 'text', 'required' => true,
    ]);
});

test('nested replicator requires at least one sub-field, with an error at the correct depth', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            [
                'handle' => 'sections', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => '',
                'replicator_fields' => [
                    ['handle' => 'items', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => ''],
                ],
            ],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.replicator_fields.0.replicator_fields']);
});

test('replicator sub-field can be a select with options and sometimes', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            [
                'handle' => 'gallery', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => '',
                'replicator_fields' => [
                    [
                        'handle' => 'size', 'display' => '', 'type' => 'select', 'required' => false,
                        'options' => 'small, medium, large', 'sometimes' => true,
                    ],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $sub = Blueprint::sole()->data['fields'][0]['replicator_fields'][0];

    expect($sub['options'])->toBe(['small', 'medium', 'large']);
    expect($sub['validate_sometimes'])->toBeTrue();
});

test('replicator sub-field can have a show-when condition scoped to its own sibling sub-fields', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            [
                'handle' => 'gallery', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => '',
                'replicator_fields' => [
                    ['handle' => 'kind', 'display' => '', 'type' => 'select', 'required' => false, 'options' => 'image, video'],
                    [
                        'handle' => 'video_url', 'display' => '', 'type' => 'text', 'required' => false,
                        'condition_mode' => 'show', 'condition_field' => 'kind', 'condition_operator' => 'equals', 'condition_value' => 'video',
                    ],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $sub = Blueprint::sole()->data['fields'][0]['replicator_fields'][1];

    expect($sub['conditions'])->toBe([
        'mode' => 'show',
        'field' => 'kind',
        'operator' => 'equals',
        'value' => 'video',
    ]);
});

test('replicator sub-field condition cannot reference a field outside the replicator', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            [
                'handle' => 'gallery', 'display' => '', 'type' => 'replicator', 'required' => false, 'options' => '',
                'replicator_fields' => [
                    [
                        'handle' => 'video_url', 'display' => '', 'type' => 'text', 'required' => false,
                        'condition_mode' => 'show', 'condition_field' => 'does_not_exist', 'condition_operator' => 'equals', 'condition_value' => 'video',
                    ],
                ],
            ],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.replicator_fields.0.condition_field']);
});

test('changing a field type resets its stale default value', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->call('create')
        ->set('fields.0.default', 'some text default')
        ->set('fields.0.type', 'toggle')
        ->assertSet('fields.0.default', '');
});

test('field builder can add, reorder and remove fields', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->call('create')
        ->assertCount('fields', 1)
        ->call('addField')
        ->assertCount('fields', 2)
        ->set('fields.0.handle', 'first')
        ->set('fields.1.handle', 'second')
        ->call('moveFieldDown', [0])
        ->assertSet('fields.0.handle', 'second')
        ->assertSet('fields.1.handle', 'first')
        ->call('removeField', [0])
        ->assertCount('fields', 1)
        ->assertSet('fields.0.handle', 'first');
});

test('field builder can add, reorder and remove fields nested inside a replicator, to any depth', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->call('create')
        ->set('fields.0.type', 'replicator')
        ->call('addReplicatorField', [0])
        ->assertCount('fields.0.replicator_fields', 1)
        ->set('fields.0.replicator_fields.0.type', 'replicator')
        ->call('addReplicatorField', [0, 0])
        ->assertCount('fields.0.replicator_fields.0.replicator_fields', 1)
        ->set('fields.0.replicator_fields.0.replicator_fields.0.handle', 'deep_field')
        ->call('addReplicatorField', [0, 0])
        ->set('fields.0.replicator_fields.0.replicator_fields.1.handle', 'deep_field_two')
        ->call('moveFieldDown', [0, 0, 0])
        ->assertSet('fields.0.replicator_fields.0.replicator_fields.0.handle', 'deep_field_two')
        ->assertSet('fields.0.replicator_fields.0.replicator_fields.1.handle', 'deep_field')
        ->call('removeField', [0, 0, 0])
        ->assertCount('fields.0.replicator_fields.0.replicator_fields', 1)
        ->assertSet('fields.0.replicator_fields.0.replicator_fields.0.handle', 'deep_field');
});

test('blueprint can be updated and fields round-trip through edit, including conditions', function () {
    $this->actingAs(User::factory()->create());

    $blueprint = Blueprint::create([
        'namespace' => null,
        'handle' => 'post',
        'data' => ['fields' => [
            ['handle' => 'type', 'display' => 'Type', 'type' => 'select', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => null, 'instructions' => null, 'validate_sometimes' => false, 'conditions' => null, 'options' => ['article', 'video']],
            ['handle' => 'video_url', 'display' => 'Video Url', 'type' => 'text', 'required' => false, 'placeholder' => null, 'default' => null, 'validation' => 'url', 'instructions' => null, 'validate_sometimes' => true, 'conditions' => ['mode' => 'show', 'field' => 'type', 'operator' => 'equals', 'value' => 'video']],
        ]],
    ]);

    Livewire::test(BlueprintManager::class)
        ->call('edit', $blueprint->id)
        ->assertSet('fields.1.sometimes', true)
        ->assertSet('fields.1.condition_mode', 'show')
        ->assertSet('fields.1.condition_field', 'type')
        ->assertSet('fields.1.condition_operator', 'equals')
        ->assertSet('fields.1.condition_value', 'video')
        ->set('handle', 'article')
        ->call('save')
        ->assertHasNoErrors();

    $blueprint->refresh();

    expect($blueprint->handle)->toBe('article');
    expect($blueprint->data['fields'][1]['conditions'])->toBe([
        'mode' => 'show', 'field' => 'type', 'operator' => 'equals', 'value' => 'video',
    ]);
    expect($blueprint->data['fields'][1]['validate_sometimes'])->toBeTrue();
});

test('blueprint can be deleted', function () {
    $this->actingAs(User::factory()->create());

    $blueprint = Blueprint::create(['namespace' => null, 'handle' => 'post', 'data' => ['fields' => []]]);

    Livewire::test(BlueprintManager::class)->call('delete', $blueprint->id);

    expect(Blueprint::find($blueprint->id))->toBeNull();
});

test('fieldset can be created', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(FieldsetManager::class)
        ->set('handle', 'seo')
        ->set('data', '{"fields":[]}')
        ->call('save')
        ->assertHasNoErrors();

    $fieldset = Fieldset::sole();

    expect($fieldset->handle)->toBe('seo');
    expect($fieldset->data)->toBe(['fields' => []]);
});

test('fieldset handle must be unique', function () {
    $this->actingAs(User::factory()->create());

    Fieldset::create(['handle' => 'seo', 'data' => []]);

    Livewire::test(FieldsetManager::class)
        ->set('handle', 'seo')
        ->set('data', '{}')
        ->call('save')
        ->assertHasErrors(['handle']);
});

test('assets field can be created scoped to an asset container', function () {
    $this->actingAs(User::factory()->create());

    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'gallery', 'display' => '', 'type' => 'assets', 'required' => false, 'options' => 'photos'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $field = Blueprint::sole()->data['fields'][0];

    expect($field['type'])->toBe('assets');
    expect($field['options'])->toBe('photos');
});

test('assets field requires an asset container to be selected', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'gallery', 'display' => '', 'type' => 'assets', 'required' => false, 'options' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.options']);
});

test('assets field container must reference an existing asset container', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'gallery', 'display' => '', 'type' => 'assets', 'required' => false, 'options' => 'does-not-exist'],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.options']);
});

test('assets field can be given a min and max item count', function () {
    $this->actingAs(User::factory()->create());

    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'gallery', 'display' => '', 'type' => 'assets', 'required' => false, 'options' => 'photos', 'min_items' => '1', 'max_items' => '3'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $field = Blueprint::sole()->data['fields'][0];

    expect($field['min_items'])->toBe(1);
    expect($field['max_items'])->toBe(3);
});

test('assets field max items cannot be less than min items', function () {
    $this->actingAs(User::factory()->create());

    AssetContainer::create(['handle' => 'photos', 'title' => 'Photos', 'disk' => 'public']);

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'gallery', 'display' => '', 'type' => 'assets', 'required' => false, 'options' => 'photos', 'min_items' => '5', 'max_items' => '2'],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.max_items']);
});

test('radio field can be created with options and a default value', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'size', 'display' => '', 'type' => 'radio', 'required' => false, 'options' => 'small, medium, large', 'default' => 'medium'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $field = Blueprint::sole()->data['fields'][0];

    expect($field['type'])->toBe('radio');
    expect($field['options'])->toBe(['small', 'medium', 'large']);
    expect($field['default'])->toBe('medium');
});

test('checkbox field stores its options and preset default values as arrays', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'toppings', 'display' => '', 'type' => 'checkbox', 'required' => false, 'options' => 'cheese, olives, mushrooms', 'default' => 'cheese, olives'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $field = Blueprint::sole()->data['fields'][0];

    expect($field['type'])->toBe('checkbox');
    expect($field['options'])->toBe(['cheese', 'olives', 'mushrooms']);
    expect($field['default'])->toBe(['cheese', 'olives']);
});

test('checkbox field can be given a min and max selection count', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'toppings', 'display' => '', 'type' => 'checkbox', 'required' => false, 'options' => 'cheese, olives, mushrooms', 'min_items' => '1', 'max_items' => '2'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $field = Blueprint::sole()->data['fields'][0];

    expect($field['min_items'])->toBe(1);
    expect($field['max_items'])->toBe(2);
});

test('checkbox field max selections cannot be less than min selections', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BlueprintManager::class)
        ->set('handle', 'post')
        ->set('fields', [
            ['handle' => 'toppings', 'display' => '', 'type' => 'checkbox', 'required' => false, 'options' => 'cheese, olives', 'min_items' => '3', 'max_items' => '1'],
        ])
        ->call('save')
        ->assertHasErrors(['fields.0.max_items']);
});
