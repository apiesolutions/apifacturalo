<?php

namespace App\Models;

use App\Models\Catalogs\IdentityDocumentType;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $with = ['identity_document_type'];
    protected $fillable = [
        'user_id',
        'identity_document_type_id',
        'number',
        'name',
        'trade_name',
        'soap_type_id',
        'soap_username',
        'soap_password',
        'certificate',
        'logo',
    ];

    public function identity_document_type()
    {
        return $this->belongsTo(IdentityDocumentType::class);
    }

    public static function active()
    {
        return Company::where('user_id', auth()->id())->first();
    }

    public static function getSoapTypeId()
    {
        return auth()->user()->company->soap_type_id;
    }
}