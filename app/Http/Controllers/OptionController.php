<?php
namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Summary;
use App\Models\Voided;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    public function create()
    {
        return view('options.form');
    }

    public function deleteDocuments(Request $request)
    {
        Summary::where('soap_type_id', '01')->delete();
        Voided::where('soap_type_id', '01')->delete();
        Document::where('soap_type_id', '01')
                ->whereIn('document_type_id', ['07', '08'])->delete();
        Document::where('soap_type_id', '01')->delete();

        return [
            'success' => true,
            'message' => 'Documentos de prueba eliminados'
        ];
    }
}