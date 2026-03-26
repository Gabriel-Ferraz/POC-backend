<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $roleId,
        ];
    }
}
