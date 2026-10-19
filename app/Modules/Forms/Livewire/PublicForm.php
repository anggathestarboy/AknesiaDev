<?php

namespace App\Modules\Forms\Livewire;

use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Models\FormSubmission;
use App\Modules\Forms\Support\BuildsSubmissionRules;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The public-facing form a site template embeds, e.g.
 *
 *     <livewire:forms::public-form handle="contact" />
 *
 * Its inputs come from the form's blueprint, so the markup always mirrors
 * whatever the admin panel's Forms/Blueprints screens define (fields, labels,
 * placeholders, required flags, conditions), and a submit stores a
 * FormSubmission for that form handle — same rules and same
 * store_submissions/redirect_url settings as the POST endpoint
 * (FormSubmissionController), just without a full page reload.
 */
class PublicForm extends Component
{
    use BuildsSubmissionRules;

    /** Handle of the Form in the admin panel, e.g. "contact". */
    public string $handle = '';

    /**
     * Field values keyed by blueprint field handle.
     *
     * @var array<string, mixed>
     */
    public array $data = [];

    /** Whether this visitor's message was already accepted this visit. */
    public bool $submitted = false;

    public function mount(string $handle = 'contact'): void
    {
        $this->handle = $handle;
        $this->data = $this->defaultData();
    }

    #[Computed]
    public function form(): Form
    {
        return Form::where('handle', $this->handle)->firstOrFail();
    }

    /**
     * The blueprint fields whose conditions pass against the values typed so
     * far — what the view renders and what the validation rules are built
     * from, so a hidden field is never required.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function visibleFields(): array
    {
        return collect($this->form->fields())
            ->filter(fn (array $field): bool => $this->fieldIsVisible($field, $this->data))
            ->values()
            ->all();
    }

    /**
     * The input type a field renders as: its own type where that implies one
     * (number/date), "email" when the field carries Laravel's email rule
     * (blueprints store "Email" as a plain text field with validation
     * "email"), plain text otherwise.
     *
     * @param  array<string, mixed>  $field
     */
    public function inputType(array $field): string
    {
        return match ($field['type']) {
            'number' => 'number',
            'date' => 'date',
            default => str_contains((string) ($field['validation'] ?? ''), 'email') ? 'email' : 'text',
        };
    }

    public function submit(): void
    {
        $form = $this->form();
        $fields = $form->fields();

        $validated = $this->validate(
            $this->submissionRules($fields, $this->data),
            [],
            $this->submissionAttributes($fields),
        );

        if ($form->storesSubmissions()) {
            FormSubmission::create([
                'form' => $form->handle,
                'data' => $validated['data'] ?? [],
            ]);
        }

        if ($redirectUrl = $form->redirectUrl()) {
            session()->flash('form_submitted', $form->handle);
            $this->redirect($redirectUrl);

            return;
        }

        $this->data = $this->defaultData();
        $this->resetValidation();
        $this->submitted = true;
    }

    /**
     * Every blueprint field's starting value (its configured default, or the
     * zero value its input needs), so wire:model always has a key to bind.
     *
     * @return array<string, mixed>
     */
    protected function defaultData(): array
    {
        return collect($this->form->fields())
            ->mapWithKeys(fn (array $field): array => [
                $field['handle'] => $this->defaultValue($field),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function defaultValue(array $field): mixed
    {
        $default = $field['default'] ?? null;

        if ($default === null) {
            return match ($field['type']) {
                'toggle' => false,
                'checkbox' => [],
                default => '',
            };
        }

        // Checkbox defaults are stored comma separated ("yes, maybe"), like
        // the blueprint editor's own "Default value(s)" input writes them.
        if ($field['type'] === 'checkbox' && is_string($default)) {
            return array_values(array_filter(array_map('trim', explode(',', $default))));
        }

        return $default;
    }

    public function render(): View
    {
        return view('forms::livewire.public-form');
    }
}
