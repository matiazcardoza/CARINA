<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return false;
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
                    // Identificadores del “gancho” al producto SILUCIA
            'id_order_silucia'   => ['required','integer'],     // idcompra de SILUCIA
            'id_product_silucia' => ['required','integer'],     // idcompradet de SILUCIA

            // Si tu front conoce el id local de la orden, lo puedes mandar (opcional),
            // 'order_id'           => ['nullable','integer','exists:orders_silucia,id'],

            // Datos del movimiento
            'movement_type'      => ['required', Rule::in(['entrada','salida'])], // entrada/salida
            'movement_date' => ['sometimes','date'],
            // 'movement_date'      => ['required','date'],
            'amount'             => ['required','numeric'],      // cantidad +/-
            // 'note'               => ['nullable','string','max:500'],

            // Campos opcionales para completar el producto si se crea en el acto
            // 'product_name'       => ['nullable','string','max:255'],
            // 'heritage_code'      => ['nullable','string','max:255'],
            // 'unit_price'         => ['nullable','numeric'],
            // 'quantity'           => ['nullable','numeric'], // stock planificado, si aplica
        ];
    }
}
