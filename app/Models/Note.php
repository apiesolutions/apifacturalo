<?php

namespace App\Models;

use App\Models\Catalogs\NoteCreditType;
use App\Models\Catalogs\NoteDebitType;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $with = ['affected_document', 'note_credit_type', 'note_debit_type'];
    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'note_type',
        'note_credit_type_id',
        'note_debit_type_id',
        'description',
        'affected_document_id',
    ];

    public function document()
    {
        return $this->hasOne(Document::class);
    }

    public function affected_document()
    {
        return $this->belongsTo(Document::class, 'affected_document_id');
    }

    public function note_credit_type()
    {
        return $this->belongsTo(NoteCreditType::class);
    }

    public function note_debit_type()
    {
        return $this->belongsTo(NoteDebitType::class);
    }
}