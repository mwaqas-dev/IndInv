<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'description', 'category_id', 'unit', 'minimum_stock', 'maximum_stock', 'status', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['minimum_stock' => 'decimal:3', 'maximum_stock' => 'decimal:3'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function currentStock(): float
    {
        return (float) $this->inventories()->sum('quantity');
    }
}
