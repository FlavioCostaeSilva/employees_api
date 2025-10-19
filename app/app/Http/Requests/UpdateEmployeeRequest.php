<?php

namespace App\Http\Requests;

use App\Rules\CpfValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employeeId = $this->route('employee');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employeeId),
            ],
            'cpf' => [
                'sometimes',
                'required',
                'string',
                'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$|^\d{11}$/',
                Rule::unique('employees', 'cpf')->ignore($employeeId),
                new CpfValidation(),
            ],
            'city' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'min:2',
            ],
            'state' => [
                'sometimes',
                'required',
                'string',
                'size:2',
                Rule::in([
                    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO',
                    'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI',
                    'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
                ]),
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('state')) {
            $this->merge([
                'state' => strtoupper($this->state),
            ]);
        }
    }
}
