<?php

namespace App\Modules\Blueprints\Support;

use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Wraps a link field's blueprint "validation" string (e.g. "url|max:255") so
 * it only applies to a typed URL: a value picked from the Entry tab is an
 * "entry::{id}" marker, which would otherwise always fail rules like "url".
 */
class LinkValidation
{
    public static function rule(string $rules): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($rules): void {
            if (is_string($value) && str_starts_with($value, 'entry::')) {
                return;
            }

            $validator = Validator::make(['link' => $value], ['link' => $rules], [], ['link' => str_replace('_', ' ', Str::afterLast($attribute, '.'))]);

            if ($validator->fails()) {
                $fail($validator->errors()->first('link'));
            }
        };
    }
}
