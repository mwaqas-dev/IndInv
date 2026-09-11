<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseHistory extends Model
{
    protected $table = 'release_history';

    protected $fillable = ['release_id', 'user_id', 'action', 'previous_status', 'new_status', 'comments'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
