<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menus' => ['required', 'array'],
            'menus.*.name' => ['required', 'string', 'max:255'],
            'menus.*.links' => ['required', 'array'],
            'menus.*.links.*.label_en' => ['required', 'string', 'max:255'],
            'menus.*.links.*.label_ar' => ['required', 'string', 'max:255'],
            'menus.*.links.*.page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'menus.*.links.*.url' => ['required', 'string', 'max:500'],
            'menus.*.links.*.parent_id' => ['nullable', 'integer'],
            'menus.*.links.*.order' => ['required', 'integer', 'min:0'],
        ];
    }
}
