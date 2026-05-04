<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Must be authenticated (handled by auth:sanctum middleware)
        return auth()->check();
    }

    public function rules(): array
    {
        $pageId = $this->route('page') instanceof \App\Models\Page 
            ? $this->route('page')->id 
            : $this->route('page');

        return [
            'site_id' => ['sometimes', 'integer', Rule::exists('sites', 'id')],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($pageId)],
            'content' => ['nullable', 'array'],
            'content_ar' => ['nullable', 'array'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_title_ar' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_description_ar' => ['nullable', 'string'],
            'keywords' => ['nullable', 'array'],
            'is_published' => ['boolean'],
            'is_translated' => ['boolean'],
            'is_home' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'site_id.exists' => 'The selected site does not exist.',
            'slug.unique' => 'This slug is already in use.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $siteId = $this->input('site_id');
            
            // If site_id is being changed, verify authorization
            if ($siteId) {
                // Admin can update pages for any site
                if ($user && $user->isAdmin()) {
                    return;
                }

                // Non-admin users can only update pages for their assigned site
                if (!$user->canAccessSite($siteId)) {
                    $validator->errors()->add('site_id', 'You are not authorized to assign pages to this site.');
                }
            }
        });
    }
}
