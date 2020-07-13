<?php
namespace App\Http\Controllers;

use App\CoreFacturalo\Helpers\Storage\StorageDocument;
use App\Models\Summary;
use Exception;

class SummaryController extends Controller
{
    use StorageDocument;

    public function downloadExternal($type, $external_id)
    {
        $summary = Summary::where('external_id', $external_id)->first();
        if(!$summary) {
            throw new Exception("El código {$external_id} es inválido, no se encontro documento relacionado");
        }
        return $this->download($type, $summary);
    }

    public function download($type, Summary $summary)
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

        $company = $summary->user->company;
        return $this->downloadStorage($summary->filename, $folder, $company->number);
    }
}