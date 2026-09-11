<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Release extends Model
{
    protected $fillable = ['number', 'product_id', 'project', 'version', 'release_type', 'description', 'status', 'created_by'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ReleaseHistory::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ReleaseApproval::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(ReleaseComponent::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
