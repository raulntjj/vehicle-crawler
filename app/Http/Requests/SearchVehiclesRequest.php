<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SearchVehiclesRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search'          => ['nullable', 'string', 'max:100'],
            'brands'          => ['nullable', 'array'],
            'brands.*'        => ['string'],
            'model'           => ['nullable', 'string', 'max:100'],
            'sources'         => ['nullable', 'array'],
            'sources.*'       => ['string'],
            'min_price'       => ['nullable', 'numeric', 'min:0'],
            'max_price'       => ['nullable', 'numeric', 'min:0'],
            'min_km'          => ['nullable', 'integer', 'min:0'],
            'max_km'          => ['nullable', 'integer', 'min:0'],
            'min_year'        => ['nullable', 'integer', 'min:1900'],
            'max_year'        => ['nullable', 'integer', 'min:1900'],
            'order_by'        => ['nullable', 'string', 'in:price,km,year_model,year_fabrication,brand,model,created_at'],
            'order_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page'        => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    /**
     * Prepara os dados de entrada antes da validação.
     * Facilita o consumo pelo frontend ao permitir strings separadas por vírgula.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeCommaSeparatedArray('brands');
        $this->mergeCommaSeparatedArray('sources');
    }

    /**
     * Converte campo de string separado por vírgula em array.
     */
    protected function mergeCommaSeparatedArray(string $key): void
    {
        if ($this->has($key) && is_string($this->input($key))) {
            $values = array_filter(
                array_map('trim', explode(',', $this->input($key)))
            );
            $this->merge([$key => array_values($values)]);
        }
    }
}
