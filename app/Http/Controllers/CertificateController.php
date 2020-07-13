<?php
namespace App\Http\Controllers;

use App\CoreFacturalo\Helpers\Certificate\GenerateCertificate;
use App\Models\Company;
use Exception;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function record()
    {
        $company = Company::active();

        return [
            'certificate' => optional($company)->certificate
        ];
    }

    public function uploadFile(Request $request)
    {
        $company = Company::active();
        try {
            if ($company) {
                if ($request->hasFile('file')) {
                    $password = $request->input('password');
                    $file = $request->file('file');
                    $pfx = file_get_contents($file);
                    $pem = GenerateCertificate::typePEM($pfx, $password);
                    $name = 'certificate_'.$company->number.'.pem';
                    if(!file_exists(storage_path('app'.DIRECTORY_SEPARATOR.'certificates'))) {
                        mkdir(storage_path('app'.DIRECTORY_SEPARATOR.'certificates'));
                    }
                    file_put_contents(storage_path('app'.DIRECTORY_SEPARATOR.'certificates'.DIRECTORY_SEPARATOR.$name), $pem);
                    $company->certificate = $name;
                    $company->save();

                    return [
                        'success' => true,
                        'message' =>  __('app.actions.upload.success'),
                    ];
                } else {
                    throw new Exception("Archivo no encontrado.");
                }
            } else {
                throw new Exception("Empresa aún no creada.");
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' =>  $e->getMessage(),
            ];
        }
    }

    public function destroy()
    {
        $company = Company::active();
        $company->certificate = null;
        $company->save();

        return [
            'success' => true,
            'message' => 'Certificado eliminado con éxito'
        ];
    }
}