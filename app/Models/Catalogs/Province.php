<?php

namespace App\Models\Catalogs;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    static function idByDescription($description)
    {
        $province = Province::where('description', $description)->first();
        if ($province) {
            return $province->id;
        }
        return '1501';
    }
}