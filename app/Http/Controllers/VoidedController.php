<?php
namespace App\Http\Controllers;

use App\CoreFacturalo\Helpers\Storage\StorageDocument;
use App\Models\Voided;
use Exception;

class VoidedController extends Controller
{
    use StorageDocument;

    public function downloadExternal($type, $external_id)
    {
        $voided = Voided::where('external_id', $external_id)->first();
        if(!$voided) {
            throw new Exception("El código {$external_id} es inválido, no se encontro documento relacionado");
        }
        return $this->download($type, $voided);
    }

    public function download($type, Voided $voided)
    {
        switch ($type) {
            case 'pdf':
                $folder = 'pdf';
                break;
            case 'xml':
                $folder = 'signed';
                break;
            case 'cdr':
                $folder = 'cdr';
                break;
            default:
                throw new Exception('Tipo de archivo a descargar es inválido');
        }

        $company = $voided->user->company;
        return $this->downloadStorage($voided->filename, $folder, $company->number);
    }
}