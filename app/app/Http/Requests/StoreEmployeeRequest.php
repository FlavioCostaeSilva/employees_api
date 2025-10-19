<?php

namespace App\Http\Requests;

use App\Rules\CpfValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3',
                'regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:employees,email',
            ],
            'cpf' => [
                'required',
                'string',
                'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$|^\d{11}$/',
                'unique:employees,cpf',
                new CpfValidation(),
            ],
            'city' => [
                'required',
                'string',
                'max:100',
                'min:2',
            ],
            'state' => [
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
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo 3 caracteres.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'name.regex' => 'O nome deve conter apenas letras e espaços.',

            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'email.max' => 'O e-mail deve ter no máximo 255 caracteres.',

            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.regex' => 'O CPF deve estar no formato 000.000.000-00 ou 00000000000.',
            'cpf.unique' => 'Este CPF já está cadastrado.',

            'city.required' => 'A cidade é obrigatória.',
            'city.min' => 'A cidade deve ter no mínimo 2 caracteres.',
            'city.max' => 'A cidade deve ter no máximo 100 caracteres.',

            'state.required' => 'O estado é obrigatório.',
            'state.size' => 'O estado deve ter 2 caracteres (sigla).',
            'state.in' => 'Informe uma sigla de estado válida (ex: SP, RJ, MG).',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Converter state para maiúsculo
        if ($this->has('state')) {
            $this->merge([
                'state' => strtoupper($this->state),
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'cpf' => 'CPF',
            'city' => 'cidade',
            'state' => 'estado',
        ];
    }
}
