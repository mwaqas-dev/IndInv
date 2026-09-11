<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = ['uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size'];

    public function attachable()
    {
        return $this->morphTo();
    }
}
