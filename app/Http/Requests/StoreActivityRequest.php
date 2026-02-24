<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
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
            'milestone_id' => 'required|exists:milestones,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bobot' => 'nullable|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'progress' => 'required|integer|min:0|max:100',
            'status' => 'required|string|in:Draft,To Do,On Progress,On Hold,Done,Cancelled',
            'uic' => 'nullable|string|max:255',
            'pic' => 'nullable|string|max:255',
        ];
    }
}
