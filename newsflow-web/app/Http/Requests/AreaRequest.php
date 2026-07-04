<?php

namespace App\Http\Requests;

use App\Services\Articles\LocationQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a local-area submission. Two shapes:
 *   US            → city + state (required), ZIP (optional 5-digit)
 *   International  → city + country (required)
 */
class AreaRequest extends FormRequest
{
    public function rules(): array
    {
        $isUs = strtoupper((string) $this->input('country_code', 'US')) === 'US';

        return [
            'country_code' => ['required', 'string', 'size:2', Rule::in(array_keys(LocationQuery::COUNTRIES))],
            'city'         => ['required', 'string', 'max:80'],
            'state'        => [Rule::requiredIf($isUs), 'nullable', 'string', 'size:2', Rule::in(array_keys(LocationQuery::US_STATES))],
            'zip'          => ['nullable', 'string', 'regex:/^\d{5}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'country_code.in' => 'That country isn’t supported yet.',
            'state.required'   => 'Please choose a state.',
            'state.in'         => 'Please choose a valid US state.',
            'zip.regex'        => 'Enter a 5-digit US ZIP code.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => strtoupper(trim((string) $this->input('country_code', 'US'))) ?: 'US',
            'state'        => $this->input('state') ? strtoupper(trim((string) $this->input('state'))) : null,
        ]);
    }
}
