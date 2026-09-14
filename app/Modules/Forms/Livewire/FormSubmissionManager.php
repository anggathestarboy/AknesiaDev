<?php

namespace App\Modules\Forms\Livewire;

use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Models\FormSubmission;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Form Submissions')]
class FormSubmissionManager extends Component
{
    public Form $form;

    public function mount(Form $form): void
    {
        $this->form = $form;
    }

    /**
     * The blueprint's top-level fields, used as this table's columns —
     * mirrors how Statamic's submissions listing shows one column per
     * field instead of a raw JSON dump.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function fields(): array
    {
        return $this->form->fields();
    }

    #[Computed]
    public function submissions()
    {
        return $this->form->submissions()->orderByDesc('id')->get();
    }

    public function delete(string $id): void
    {
        FormSubmission::where('id', $id)->where('form', $this->form->handle)->delete();

        Flux::toast(variant: 'success', text: __('Submission deleted.'));

        unset($this->submissions);
    }

    public function render(): View
    {
        return view('forms::livewire.form-submission-manager');
    }
}
