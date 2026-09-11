<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $fillable = ['product_id', 'location_id', 'quantity', 'reserved_quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'reserved_quantity' => 'decimal:3'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function available(): float
    {
        return (float) $this->quantity - (float) $this->reserved_quantity;
    }
}
