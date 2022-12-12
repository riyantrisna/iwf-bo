<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventGroup extends Model
{
    protected $table = 'event_group';
    protected $primaryKey = 'id';

    public $timestamps = false;
}
