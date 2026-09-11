<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseComponent extends Model
{
    protected $fillable = ['release_id', 'name', 'version', 'notes'];
}
