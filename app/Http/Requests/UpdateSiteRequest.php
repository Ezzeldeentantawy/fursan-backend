<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $siteId = $this->route('id'); // Get site ID from route parameter for unique rule ignore

        return [
            'name' => 'sometimes|string|max:255',
            'domain' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                "unique:sites,domain,{$siteId}",
            ],
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Add custom validation logic.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = auth()->user();

            // Only allow site updates if user is admin
            if (!$user->is_admin && $this->hasAny(['name', 'domain', 'is_active'])) {
                $validator->errors()->add(
                    'permission',
                    'Only administrators can update site settings.'
                );
            }
        });
    }
}
