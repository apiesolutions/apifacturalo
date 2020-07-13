<?php
namespace App\Http\Controllers\Api;

use App\CoreFacturalo\Facturalo;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('transform.input:document', ['only' => ['store']]);
    }

    public function store(Request $request)
    {
        if(!$request->input('success')) {
            return [
                'success' => false,
                'message' => $request->input('message'),
                'code' => $request->input('code')
            ];
        }

        $facturalo = new Facturalo(Company::active());
        $facturalo->setInputs($request->all());

        DB::transaction(function () use($facturalo) {
            $facturalo->save();
            $facturalo->createXmlAndSign();
            $facturalo->createPdf();
        });

        $send = ($request->input('document.group_id') === '01')?true:false;
        $send = $send && $request->input('actions.send_xml_signed');
        $res = ($send)?$facturalo->sendXml($facturalo->getXmlSigned()):[];

        $document = $facturalo->getDocument();
        return [
            'success' => true,
            'data' => [
                'number' => $document->number_full,
                'filename' => $document->filename,
                'external_id' => $document->external_id,
                'number_to_letter' => $document->number_to_letter,
                'hash' => $document->hash,
                'qr' => $document->qr,
            ],
            'links' => [
                'xml' => $document->download_external_xml,
                'pdf' => $document->download_external_pdf,
                'cdr' => ($send)?$document->download_external_cdr:'',
            ],
            'response' => $res
        ];
    }

    public function send(Request $request)
    {
        if($request->has('external_id')) {
            $facturalo = new Facturalo(Company::active());
            $document = Document::where('external_id', $request->input('external_id'))->first();
            $facturalo->setDocument($document);
            $res = $facturalo->loadAndSendXml();
            return [
                'success' => true,
                'data' => [
                    'number' => $document->number_full,
                    'filename' => $document->filename,
                    'external_id' => $document->external_id,
                ],
                'links' => [
                    'cdr' => $document->download_external_cdr,
                ],
                'response' => $res
            ];
        }
    }

    public function getLinks(Request $request) {

        $company = Company::where('number', $request->input('company_number'))->first();

        if(!$company) {
            return [
                'success' => false,
                'message' => 'El RUC del emisor no se encuentra registrado.'
            ];
        }

        $document = Document::where('user_id', $company->user_id)
            ->where('document_type_id', $request->input('document_type_id'))
            ->where('series', $request->input('series'))
            ->where('number', $request->input('number_document'))
            ->where('total', $request->input('total'))
            ->first();

        if(!$document) {
            return [
                'success' => false,
                'message' => 'Comprobante no encontrado.'
            ];
        }

        return [
            'success' => true,
            'data' => [
                'external_id' => $document->external_id
            ],
            'links' => [
                'pdf' => $document->download_external_pdf,
                'xml' => $document->download_external_xml
            ],
        ];
    }
}