<?php
namespace App\Http\Controllers\Api;

use App\Core\Services\Dni\Dni;
use App\Core\Services\Ruc\Sunat;
use App\Http\Controllers\Controller;
use App\Models\Catalogs\Department;
use App\Models\Catalogs\District;
use App\Models\Catalogs\Province;

class ServiceController extends Controller
{
    public function ruc($number)
    {
        $service = new Sunat();
        $res = $service->get($number);
        if ($res) {
            return [
                'success' => true,
                'data' => [
                    'name' => $res->razonSocial,
                    'trade_name' => $res->nombreComercial,
                    'address' => $res->direccion,
                    'phone' => implode(' / ', $res->telefonos),
                    'department' => ($res->departamento)?:'LIMA',
                    'department_id' => Department::idByDescription($res->departamento),
                    'province' => ($res->provincia)?:'LIMA',
                    'province_id' => Province::idByDescription($res->provincia),
                    'district' => ($res->distrito)?:'LIMA',
                    'district_id' => District::idByDescription($res->distrito),
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => $service->getError()
            ];
        }
    }

    public function dni($number)
    {
        $res = Dni::search($number);

        return $res;
    }
}