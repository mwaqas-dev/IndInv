<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseApproval extends Model
{
    protected $fillable = ['release_id', 'approver_id', 'decision', 'comments', 'previous_status', 'new_status', 'ip_address'];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
