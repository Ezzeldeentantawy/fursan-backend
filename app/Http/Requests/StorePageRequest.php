<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Must be authenticated (handled by auth:sanctum middleware)
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'site_id' => ['required', 'integer', Rule::exists('sites', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')],
            'content' => ['nullable', 'array'],
            'content_ar' => ['nullable', 'array'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_title_ar' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_description_ar' => ['nullable', 'string'],
            'keywords' => ['nullable', 'array'],
            'keywords.en' => ['nullable', 'array'],
            'keywords.en.*' => ['string', 'max:255'],
            'keywords.ar' => ['nullable', 'array'],
            'keywords.ar.*' => ['string', 'max:255'],
            'is_published' => ['boolean'],
            'is_translated' => ['boolean'],
            'is_home' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'site_id.required' => 'The site ID is required.',
            'site_id.exists' => 'The selected site does not exist.',
            'title.required' => 'The page title is required.',
            'slug.unique' => 'This slug is already in use for the selected site.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug from title if not provided
        if (!$this->slug && $this->title) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->title),
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if user is authorized to create pages for this site
            $user = $this->user();
            $siteId = $this->input('site_id');
            
            if ($user && $siteId) {
                // Admin can create pages for any site
                if ($user->isAdmin()) {
                    return;
                }

                // Non-admin users can only create pages for their assigned site
                if (!$user->canAccessSite($siteId)) {
                    $validator->errors()->add('site_id', 'You are not authorized to create pages for this site.');
                }
            }
        });
    }
}
