<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Landingpage extends Model
{
    protected $table = 'homescreen';
    protected $primaryKey = 'id';

    public $timestamps = false;
}
