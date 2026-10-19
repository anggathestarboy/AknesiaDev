<?php

namespace App\Modules\Forms\Http\Controllers;

use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Models\FormSubmission;
use App\Modules\Forms\Support\BuildsSubmissionRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles the actual <form method="POST"> submission for a Form — the
 * alternative to this endpoint for templates that prefer a Livewire form is
 * the PublicForm component, and both share the same rules (see
 * BuildsSubmissionRules). This just validates against the form's blueprint
 * fields and stores the result, mirroring Statamic's generic form-submit
 * route.
 */
class FormSubmissionController
{
    use BuildsSubmissionRules;

    public function store(Request $request, Form $form): RedirectResponse
    {
        $validated = $request->validate(
            $this->submissionRules($form->fields(), (array) $request->input('data', [])),
            [],
            $this->submissionAttributes($form->fields())
        );

        if ($form->storesSubmissions()) {
            FormSubmission::create([
                'form' => $form->handle,
                'data' => $validated['data'] ?? [],
            ]);
        }

        $redirectUrl = $form->redirectUrl();

        return $redirectUrl
            ? redirect($redirectUrl)->with('form_submitted', $form->handle)
            : back()->with('form_submitted', $form->handle);
    }
}
