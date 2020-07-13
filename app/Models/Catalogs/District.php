<?php

namespace App\Models\Catalogs;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    static function idByDescription($description, $province_id)
    {
        $district = District::where('description', $description)
                            ->where('province_id', $province_id);
        if ($district) {
            return $district->id;
        }
        return '150101';
    }
}