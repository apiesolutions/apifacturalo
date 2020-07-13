<?php

namespace App\Models\Catalogs;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    static function idByDescription($description)
    {
        $department = Department::where('description', $description)->first();
        if ($department) {
            return $department->id;
        }
        return '15';
    }
}