<?php
namespace App\Http\Controllers;

use App\CoreFacturalo\Helpers\Storage\StorageDocument;
use App\Http\Resources\DocumentCollection;
use App\Models\Document;
use Exception;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    use StorageDocument;

    public function index()
    {
        return view('documents.index');
    }

    public function columns()
    {
        return [
            'id' => 'Código',
            'number' => 'Número'
        ];
    }

    public function records(Request $request)
    {
        $records = Document::where($request->get('column'), 'like', "%{$request->get('value')}%")
                            ->whereUser()
                            ->orderBy('series')
                            ->orderBy('number', 'desc');

        return new DocumentCollection($records->paginate(env('ITEMS_PER_PAGE', 10)));
    }

    public function downloadExternal($type, $external_id)
    {
        $document = Document::where('external_id', $external_id)->first();
        if(!$document) {
            throw new Exception("El código {$external_id} es inválido, no se encontro documento relacionado");
        }
        return $this->download($type, $document);
    }

    public function download($type, Document $document)
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

        $company = $document->user->company;
        return $this->downloadStorage($document->filename, $folder, $company->number);
    }
}