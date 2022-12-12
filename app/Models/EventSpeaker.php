<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSpeaker extends Model
{
    protected $table = 'event_speaker';
    protected $primaryKey = 'id';

    public $timestamps = false;
}
