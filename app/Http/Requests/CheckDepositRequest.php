<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckDepositRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'check_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'title' => 'required|string|min:1',
        ];
    }
}
