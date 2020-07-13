<?php
namespace App\Http\Controllers\Api;

use App\CoreFacturalo\Facturalo;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Voided;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoidedController extends Controller
{
    public function __construct()
    {
        $this->middleware('transform.input:voided', ['only' => ['store']]);
    }

    public function store(Request $request)
    {
        $facturalo = new Facturalo(Company::active());
        $facturalo->setInputs($request->all());

        DB::transaction(function () use($facturalo) {
            $facturalo->save();
            $facturalo->createXmlAndSign();
        });

        $facturalo->sendXml($facturalo->getXmlSigned());
        $voided = $facturalo->getDocument();

        return [
            'success' => true,
            'external_id' => $voided->external_id,
            'ticket' => $voided->ticket,
        ];
    }

    public function status(Request $request)
    {
        if($request->has('external_id')) {
            $external_id = $request->input('external_id');
            $voided = Voided::where('external_id', $external_id)
                                ->where('user_id', auth()->id())
                                ->first();
            if(!$voided) {
                throw new Exception("El código {$external_id} es inválido, no se encontró anulación relacionada");
            }
        } elseif ($request->has('ticket')) {
            $ticket = $request->input('ticket');
            $voided = Voided::where('ticket', $ticket)
                                ->where('user_id', auth()->id())
                                ->first();
            if(!$voided) {
                throw new Exception("El ticket {$ticket} es inválido, no se encontró anulación relacionada");
            }
        } else {
            throw new Exception('Es requerido el código externo o ticket');
        }

        $facturalo = new Facturalo($voided->user->company);
        $facturalo->setDocument($voided);
        $res = $facturalo->statusSummary($voided->ticket);

        return [
            'success' => true,
            'data' => [
                'filename' => $voided->filename,
                'external_id' => $voided->external_id
            ],
            'links' => [
                'xml' => $voided->download_external_xml,
//                'pdf' => $summary->download_external_pdf,
                'cdr' => $voided->download_external_cdr,
            ],
            'response' => $res
        ];
    }
}