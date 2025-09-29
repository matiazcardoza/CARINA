<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovementPecosaRequest extends FormRequest
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
            // 'id_order_silucia'   => ['required','integer'],     // idcompra de SILUCIA
            // 'id_container_silucia'   => ['required','string','max:20','regex:/^\d+$/'],
            // 'id_pecosa_silucia'   => ['required','string','max:20','regex:/^\d+$/'],
            // 'id_item_pecosa_silucia' => ['required','integer'],     // idcompradet de SILUCIA
            // 'id_product_silucia' => ['required','integer'],  
            // Si tu front conoce el id local de la orden, lo puedes mandar (opcional),
            // 'order_id'           => ['nullable','integer','exists:orders_silucia,id'],

            // Datos del movimiento
            'movement_type'      => ['required', Rule::in(['entrada','salida'])], // entrada/salida
            'movement_date'      => ['sometimes','date'],
            // 'movement_date'      => ['required','date'],
            'amount'             => ['numeric'],      // cantidad +/-
            'observations'       => ['nullable', 'string', 'max:5000'],      // cantidad +/-
            // 'note'               => ['nullable','string','max:500'],

            // Campos opcionales para completar el producto si se crea en el acto
            // 'product_name'       => ['nullable','string','max:255'],
            // 'heritage_code'      => ['nullable','string','max:255'],
            // 'unit_price'         => ['nullable','numeric'],
            // 'quantity'           => ['nullable','numeric'], // stock planificado, si aplica
                    // nuevo: adjuntar personas por DNI (opcional)
            // 'people_dnis'        => ['sometimes','array'],
            // 'people_dnis.*'      => ['required','string','regex:/^\d{8}$/'],
            'people_ids' => ['nullable', 'array'],
            'people_ids.*' => ['numeric'],


            // para guarddar los nuevo datos de silucia
            // 'silucia_pecosa.anio'            => ['bail','digits:4'],
            // 'silucia_pecosa.numero'          => ['bail','string','max:20','regex:/^\d+$/'],
            // 'silucia_pecosa.fecha'           => ['bail','date'],
            // 'silucia_pecosa.prod_proy'       => ['nullable','string','max:20','regex:/^\d+$/'],
            // 'silucia_pecosa.cod_meta'        => ['nullable','string','max:20'],
            // 'silucia_pecosa.desmeta'         => ['nullable','string'],
            // 'silucia_pecosa.desuoper'        => ['nullable','string'],
            // 'silucia_pecosa.destipodestino'  => ['nullable','string','max:50'],
            // 'silucia_pecosa.item'            => ['bail','string'],
            // 'silucia_pecosa.desmedida'       => ['bail','string','max:50'],
            // 'silucia_pecosa.idsalidadet'     => ['bail','integer','min:1'],
            // 'silucia_pecosa.cantidad'        => ['bail','numeric'],
            // 'silucia_pecosa.precio'          => ['bail','numeric'],
            // 'silucia_pecosa.tipo'            => ['bail','string','max:20'],
            // 'silucia_pecosa.saldo'           => ['bail','numeric'],
            // 'silucia_pecosa.total'           => ['bail','numeric'],
            // 'silucia_pecosa.numero_origen'   => ['bail','string','max:20','regex:/^\d+$/'],

        ];
    }
}
