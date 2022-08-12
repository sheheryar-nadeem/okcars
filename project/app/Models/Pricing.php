<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    protected $fillable = ['name', 'minimum', 'maximum'];
    public $timestamps = false;

}
