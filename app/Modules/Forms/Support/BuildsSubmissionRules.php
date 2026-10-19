<?php

namespace App\Modules\Forms\Support;

use App\Modules\Blueprints\Support\EvaluatesFieldConditions;
use Illuminate\Validation\Rule;

/**
 * Validation rules and friendly attribute names for a Form's blueprint
 * fields, shared by the public POST endpoint (FormSubmissionController) and
 * the public Livewire component (PublicForm) so both accept exactly the same
 * data for the same form — same required/type rules, same per-field
 * "validation" string, and the same field visibility condition (see
 * EvaluatesFieldConditions) deciding whether a field is validated at all.
 */
trait BuildsSubmissionRules
{
    use EvaluatesFieldConditions;

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $submittedData
     * @return array<string, mixed>
     */
    protected function submissionRules(array $fields, array $submittedData = []): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $key = "data.{$field['handle']}";

            if (! $this->fieldIsVisible($field, $submittedData)) {
                $rules[$key] = ['nullable'];

                continue;
            }

            $fieldRules = [($field['required'] ?? false) ? 'required' : 'nullable'];

            $fieldRules[] = match ($field['type']) {
                'number' => 'numeric',
                'toggle' => 'boolean',
                'date' => 'date',
                'select', 'radio' => Rule::in($field['options'] ?? []),
                'checkbox' => 'array',
                default => 'string',
            };

            if (! empty($field['validation'])) {
                $fieldRules[] = $field['validation'];
            }

            $rules[$key] = $fieldRules;

            if ($field['type'] === 'checkbox') {
                $rules["{$key}.*"] = [Rule::in($field['options'] ?? [])];
            }
        }

        return $rules;
    }

    /**
     * Maps each "data.*" validation key to its field's display label, so a
     * validation message reads "The Email field is required." instead of "The
     * data.email field is required." — the same fix every blueprint-driven
     * Livewire form applies.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, string>
     */
    protected function submissionAttributes(array $fields): array
    {
        return collect($fields)->mapWithKeys(fn (array $field): array => [
            "data.{$field['handle']}" => $field['display'] ?? $field['handle'],
        ])->all();
    }
}
