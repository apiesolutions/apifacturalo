<?php

namespace App\Http\Middleware;

use App\Models\Catalogs\Country;
use App\Models\Catalogs\Department;
use App\Models\Catalogs\District;
use App\Models\Catalogs\IdentityDocumentType;
use App\Models\Catalogs\Province;
use App\Models\Company;
use App\Models\Document;
use Closure;
use Exception;

class TransformInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $originalAttributes = $this->originalAttribute($request->all());
        $request->replace($originalAttributes);
        return $next($request);
    }

    private function originalAttribute($inputs)
    {
        try {
            $items = [];
            foreach ($inputs['items'] as $row)
            {
                $attributes = [];
                if(array_key_exists('datos_adicionales', $row)) {
                    foreach ($row['datos_adicionales'] as $add)
                    {
                        $attributes[] = [
                            'code' => $add['codigo'],
                            'name' => $add['nombre'],
                            'value' => array_key_exists('valor', $add)?$add['valor']:null,
                            'start_date' => array_key_exists('fecha_inicio', $add)?$add['fecha_inicio']:null,
                            'end_date' => array_key_exists('fecha_fin', $add)?$add['fecha_fin']:null,
                            'duration' => array_key_exists('duracion', $add)?$add['duracion']:null,
                        ];
                    }
                }

                $item_charges = $this->getChargesDiscounts($row, 'cargos');
                $item_discounts = $this->getChargesDiscounts($row, 'descuentos');

                $item = [
                    'description' => $row['descripcion'],
                    'item_type_id' => '01',
                    'internal_id' => array_key_exists('codigo_interno', $row)?$row['codigo_interno']:null,
                    'item_code' => array_key_exists('codigo_producto_sunat', $row)?$row['codigo_producto_sunat']:null,
                    'item_code_gs1' => array_key_exists('codigo_producto_gsl', $row)?$row['codigo_producto_gsl']:null,
                    'unit_type_id' => strtoupper($row['unidad_de_medida']),
                ];

                $items[] = [
                    'item' => $item,
                    'quantity' => $row['cantidad'],
                    'unit_value' => $row['valor_unitario'],
                    'price_type_id' => $row['codigo_tipo_precio'],
                    'unit_price' => $row['precio_unitario'],

                    'affectation_igv_type_id' => $row['codigo_tipo_afectacion_igv'],
                    'total_base_igv' => $row['total_base_igv'],
                    'percentage_igv' => $row['porcentaje_igv'],
                    'total_igv' => $row['total_igv'],

                    'system_isc_type_id' => array_key_exists('codigo_tipo_sistema_isc', $row)?$row['codigo_tipo_sistema_isc']:null,
                    'total_base_isc' => array_key_exists('total_base_isc', $row)?$row['total_base_isc']:0,
                    'percentage_isc' => array_key_exists('porcentaje_isc', $row)?$row['porcentaje_isc']:0,
                    'total_isc' => array_key_exists('total_isc', $row)?$row['total_isc']:0,

                    'total_base_other_taxes' => array_key_exists('total_base_otros_impuestos', $row)?$row['total_base_otros_impuestos']:0,
                    'percentage_other_taxes' => array_key_exists('porcentaje_otros_impuestos', $row)?$row['porcentaje_otros_impuestos']:0,
                    'total_other_taxes' => array_key_exists('total_otros_impuestos', $row)?$row['total_otros_impuestos']:0,

                    'total_taxes' => $row['total_impuestos'],
                    'total_value' => $row['total_valor_item'],
                    'total' => $row['total_item'],

                    'charges' => $item_charges,
                    'discounts' => $item_discounts,
                    'attributes' => $attributes,
                ];
            }

            $prepayments = [];
            if (array_key_exists('anticipos', $inputs)) {
                foreach ($inputs['anticipos'] as $row)
                {
                    $prepayments[] = [
                        'number' => $row['numero'],
                        'document_type_id' => $row['codigo_tipo_documento'],
                        'amount' => $row['monto'],
                    ];
                }
            }

            $related_documents = [];
            if (array_key_exists('documentos_relacionados', $inputs)) {
                foreach ($inputs['documentos_relacionados'] as $row)
                {
                    $related_documents[] = [
                        'number' => $row['numero'],
                        'document_type_id' => $row['codigo_tipo_documento']
                    ];
                }
            }

            $perception = null;
            if(array_key_exists('percepcion', $inputs)) {
                $data = $inputs['percepcion'];
                $perception = [
                    'code' => $data['codigo'],
                    'percentage' => $data['porcentaje'],
                    'amount' => $data['monto'],
                    'base' => $data['base'],
                ];
            }

            $detraction = null;
            if(array_key_exists('detraccion', $inputs)) {
                $data = $inputs['detraccion'];
                $detraction = [
                    'detraction_type_id' => $data['codigo'],
                    'percentage' => $data['porcentaje'],
                    'amount' => $data['monto'],
                    'payment_method_id' => $data['codigo_metodo_pago'],
                    'bank_account' => $data['cuenta_bancaria'],
                ];
            }

            $optional = [];
            if(array_key_exists('extras', $inputs)) {
                $data = $inputs['extras'];
                $optional = [
                    'observations' => array_key_exists('observaciones', $data)?$data['observaciones']:null,
                    'method_payment' => array_key_exists('forma_de_pago', $data)?$data['forma_de_pago']:null,
                    'salesman' => array_key_exists('vendedor', $data)?$data['vendedor']:null,
                    'box_number' => array_key_exists('caja', $data)?$data['caja']:null ,
                    'format_pdf' => array_key_exists('formato_pdf', $data)?$data['formato_pdf']:'a4'
                ];
            }

            $charges = $this->getChargesDiscounts($inputs, 'cargos');
            $discounts = $this->getChargesDiscounts($inputs, 'descuentos');

            //  Total Variables
            $total_other_charges = array_key_exists('total_otros_cargos', $inputs['totales'])?$inputs['totales']['total_otros_cargos']:0;
            $total_exportation = array_key_exists('total_exportacion', $inputs['totales'])?$inputs['totales']['total_exportacion']:0;
            $total_taxed = array_key_exists('total_operaciones_gravadas', $inputs['totales'])?$inputs['totales']['total_operaciones_gravadas']:0;
            $total_unaffected = array_key_exists('total_operaciones_inafectas', $inputs['totales'])?$inputs['totales']['total_operaciones_inafectas']:0;
            $total_exonerated = array_key_exists('total_operaciones_exoneradas', $inputs['totales'])?$inputs['totales']['total_operaciones_exoneradas']:0;
            $total_igv = array_key_exists('total_igv', $inputs['totales'])?$inputs['totales']['total_igv']:0;
            $total_base_isc = array_key_exists('total_base_isc', $inputs['totales'])?$inputs['totales']['total_base_isc']:0;
            $total_isc = array_key_exists('total_isc', $inputs['totales'])?$inputs['totales']['total_isc']:0;
            $total_base_other_taxes = array_key_exists('total_base_otros_impuestos', $inputs['totales'])?$inputs['totales']['total_base_otros_impuestos']:0;
            $total_other_taxes = array_key_exists('total_otros_impuestos', $inputs['totales'])?$inputs['totales']['total_otros_impuestos']:0;
            $total_taxes = array_key_exists('total_impuestos', $inputs['totales'])?$inputs['totales']['total_impuestos']:0;
            $total_free = array_key_exists('total_operaciones_gratuitas', $inputs['totales'])?$inputs['totales']['total_operaciones_gratuitas']:0;
            $total_discount = array_key_exists('total_descuentos', $inputs['totales'])?$inputs['totales']['total_descuentos']:0;
            $total_charge = array_key_exists('total_cargos', $inputs['totales'])?$inputs['totales']['total_cargos']:0;
            $total_prepayment = array_key_exists('total_anticipos', $inputs['totales'])?$inputs['totales']['total_anticipos']:0;
            $total_value = array_key_exists('total_valor', $inputs['totales'])?$inputs['totales']['total_valor']:0;
            $total = $inputs['totales']['total_venta'];

            // Date Variables
            $date_of_issue = $inputs['fecha_de_emision'];
            $time_of_issue = $inputs['hora_de_emision'];
            $date_of_due = array_key_exists('fecha_de_vencimiento', $inputs)?$inputs['fecha_de_vencimiento']:null;

            // Document Variables
            $document_type_id = $inputs['codigo_tipo_documento'];

            if(!in_array($document_type_id, ['01', '03', '07', '08'])) {
                throw new Exception("El código {$document_type_id} de tipo de documento es incorrecto.");
            }

            $ubl_version = "2.1";
            $currency_type_id = $inputs['codigo_tipo_moneda'];
            $document_series = $inputs['serie_documento'];
            $document_number = $inputs['numero_documento'];

            $doc = Document::where('document_type_id', $document_type_id)
                ->where('series', $document_series)
                ->where('number', $document_number)
                ->first();

            if($doc) {
                throw new Exception("El documento {$document_series}-{$document_number} ya se encuentra registrado.");
            }

            $purchase_order = array_key_exists('numero_orden_de_compra', $inputs)?$inputs['numero_orden_de_compra']:null;

            // Establishment
            $data = $inputs['datos_del_emisor'];
            $establishment = [
                'country_id' => $data['codigo_pais'],
                'district_id' => $data['ubigeo'],
                'urbanization' => array_key_exists('urbanizacion', $data)?$data['urbanizacion']:null,
                'address' => $data['direccion'],
                'email' => $data['correo_electronico'],
                'telephone' => $data['telefono'],
                'code' => $data['codigo_del_domicilio_fiscal']
            ];

            // Customer
            $data = $inputs['datos_del_cliente_o_receptor'];
            $customer = [
                'identity_document_type_id' => $data['codigo_tipo_documento_identidad'],
                'number' => $data['numero_documento'],
                'name' => $data['apellidos_y_nombres_o_razon_social'],
                'trade_name' => array_key_exists('nombre_comercial', $data)?$data['nombre_comercial']:null,
                'country_id' => array_key_exists('codigo_pais', $data)?$data['codigo_pais']:null,
                'district_id' => array_key_exists('ubigeo', $data)?$data['ubigeo']:null,
                'address' => array_key_exists('direccion', $data)?$data['direccion']:null,
                'email' => array_key_exists('correo_electronico', $data)?$data['correo_electronico']:null,
                'telephone' => array_key_exists('telephone', $data)?$data['telefono']:null,
            ];

            $document_base = [];
            $group_id = null;
            $type = null;
            // Invoice Variables
            $operation_type_id = array_key_exists('codigo_tipo_operacion', $inputs)?$inputs['codigo_tipo_operacion']:null;
            // Note Variables
            $affected_document_series = array_key_exists('serie_de_documento_afectado', $inputs)?$inputs['serie_de_documento_afectado']:null;
            $affected_document_number = array_key_exists('numero_de_documento_afectado', $inputs)?$inputs['numero_de_documento_afectado']:null;
            $affected_document_type_id = array_key_exists('tipo_de_documento_afectado', $inputs)?$inputs['tipo_de_documento_afectado']:null;
            $note_credit_or_debit_type_id = array_key_exists('codigo_de_tipo_de_la_nota', $inputs)?$inputs['tipo_de_operacion']:null;
            $description = array_key_exists('motivo_o_sustento_de_la_nota', $inputs)?$inputs['motivo_o_sustento_de_la_nota']:null;

            // Acciones
            $send_email = array_key_exists('enviar_email', $inputs)?(bool)$inputs['enviar_email']:false;

            /*
             * Invoice
             */
            if (in_array($document_type_id, ['01', '03'])) {
                $document_base = [
                    'operation_type_id' => $operation_type_id,
                    'date_of_due' => $date_of_due,
                    'total_free' => $total_free,
                    'total_discount' => $total_discount,
                    'total_charge' => $total_charge,
                    'total_prepayment' => $total_prepayment,
                    'total_value' => $total_value,

                    'charges' => $charges,
                    'discounts' => $discounts,
                    'perception' => $perception,
                    'detraction' => $detraction,
                    'prepayments' => $prepayments,
                ];
                $group_id = ($document_type_id === '01')?'01':'02';
                $type = 'invoice';
            }

            /*
             * Note Credit, Note Debit
             */
            if (in_array($document_type_id, ['07', '08'])) {
                if ($document_type_id === '07') {
                    $note_type = 'credit';
                    $note_credit_type_id = $note_credit_or_debit_type_id;
                    $note_debit_type_id = null;
                    $type = 'credit';
                } else {
                    $note_type = 'debit';
                    $note_credit_type_id = null;
                    $note_debit_type_id = $note_credit_or_debit_type_id;
                    $type = 'debit';
                }

                $affected_document = Document::where('document_type_id', $affected_document_type_id)
                    ->where('series', $affected_document_series)
                    ->where('number', $affected_document_number)
                    ->where('state_type_id', '05')
                    ->first();
                if ($affected_document) {
                    $document_base = [
                        'note_type' => $note_type,
                        'note_credit_type_id' => $note_credit_type_id,
                        'note_debit_type_id' => $note_debit_type_id,
                        'description' => $description,
                        'affected_document_id' => $affected_document->id,
                        'total_prepayment' => $total_prepayment,
                    ];
                    $group_id = ($affected_document_type_id === '01')?'01':'02';
                } else {
                    throw new Exception("El documento afectado {$affected_document_series}-{$affected_document_number} no se encuentra registrado, o no se encuentra aceptado.");
                }
            }

            $guides = [];
            if (array_key_exists('guias', $inputs)) {
                foreach ($inputs['guias'] as $row)
                {
                    $guides[] = [
                        'number' => $row['numero'],
                        'document_type_id' => $row['tipo_de_documento'],
                    ];
                }
            }

            $legends = [];
            if (array_key_exists('leyendas', $inputs)) {
                foreach ($inputs['leyendas'] as $row)
                {
                    $legends[] = [
                        'code' => $row['codigo'],
                        'value' => $row['valor'],
                    ];
                }
            }

            $actions = [
                'send_email' => $send_email
            ];

//            dd($document_base);
            $original_attributes = [
                'type' => $type,
                'document' => [
                    'user_id' => auth()->id(),
                    'establishment' => $this->setEstablishment($establishment),
                    'external_id' => '',
                    'state_type_id' => '01',
                    'ubl_version' => $ubl_version,
                    'soap_type_id' => $this->getSoapType(),
                    'group_id' => $group_id,
                    'document_type_id' => $document_type_id,
                    'date_of_issue' => $date_of_issue,
                    'time_of_issue' => $time_of_issue,
                    'customer' => $this->setCustomer($customer),
                    'series' => $document_series,
                    'number' => $document_number,
                    'currency_type_id' => $currency_type_id,
                    'purchase_order' => $purchase_order,

                    'total_other_charges' => $total_other_charges,
                    'total_exportation' => $total_exportation,
                    'total_taxed' => $total_taxed,
                    'total_unaffected' => $total_unaffected,
                    'total_exonerated' => $total_exonerated,
                    'total_igv' => $total_igv,
                    'total_base_isc' => $total_base_isc,
                    'total_isc' => $total_isc,
                    'total_base_other_taxes' => $total_base_other_taxes,
                    'total_other_taxes' => $total_other_taxes,
                    'total_taxes' => $total_taxes,
                    'total' => $total,

                    'legends' => $legends,
                    'guides' => $guides,
                    'related_documents' => $related_documents,
                    'optional' => $optional,

                    'items' => $items,
                    'filename' => '',
                    'hash' => '',
                    'qr' => '',
                ],
                'document_base' => $document_base,
                'actions' => $actions,
                'success' => true,
            ];

            return $original_attributes;

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function getChargesDiscounts($inputs, $type)
    {
        $data = [];
        if(array_key_exists($type, $inputs)) {
            foreach ($inputs[$type] as $add)
            {
                $data[] = [
                    'code' => $add['codigo'],
                    'description' => $add['descripcion'],
                    'percentage' => $add['porcentaje'],
                    'amount' => $add['monto'],
                    'base' => $add['base'],
                ];
            }
        }

        return $data;
    }

    private function getSoapType()
    {
        $company = Company::byUser();
        return $company->soap_type_id;
    }

    private function setEstablishment($data)
    {
        $department_id = null;
        $province_id = null;
        $district_id = $data['district_id'];
        $country_id = $data['country_id'];

        if ($district_id) {
            $province_id = substr($district_id, 0 ,4);
            $department_id = substr($district_id, 0 ,2);
        }

        return [
            'country_id' => $country_id,
            'country' => [
                'id' => $country_id,
                'description' => Country::find($country_id)->description,
            ],
            'department_id' => $department_id,
            'department' => [
                'id' => $department_id,
                'description' => Department::find($department_id)->description,
            ],
            'province_id' => $province_id,
            'province' => [
                'id' => $province_id,
                'description' => Province::find($province_id)->description,
            ],
            'district_id' => $district_id,
            'district' => [
                'id' => $district_id,
                'description' => District::find($district_id)->description,
            ],
            'urbanization' => $data['urbanization'],
            'address' => $data['address'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'code' => $data['code'],
        ];
    }

    private function setCustomer($data)
    {
        $department_id = null;
        $province_id = null;
        $district_id = $data['district_id'];
        $country_id = $data['country_id'];
        $identity_document_type_id = $data['identity_document_type_id'];

        if ($district_id) {
            $province_id = substr($district_id, 0 ,4);
            $department_id = substr($district_id, 0 ,2);
        }

        return [
            'identity_document_type_id' => $identity_document_type_id,
            'identity_document_type' => [
                'id' => $identity_document_type_id,
                'description' => IdentityDocumentType::find($identity_document_type_id)->description,
            ],
            'number' => $data['number'],
            'name' => $data['name'],
            'country_id' => $country_id,
            'country' => [
                'id' => $country_id,
                'description' => Country::find($country_id)->description,
            ],
            'department_id' => $department_id,
            'department' => [
                'id' => $department_id,
                'description' => Department::find($department_id)->description,
            ],
            'province_id' => $province_id,
            'province' => [
                'id' => $province_id,
                'description' => Province::find($province_id)->description,
            ],
            'district_id' => $district_id,
            'district' => [
                'id' => $district_id,
                'description' => District::find($district_id)->description,
            ],
            'address' => $data['address'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
        ];
    }
}