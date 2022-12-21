<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupEvent extends Model
{
    protected $table = 'groups';
    protected $primaryKey = 'id';

    public $timestamps = false;
}
