<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(private AuditService $audit) {}

    public function stockIn(Product $product, int $locationId, float $quantity, string $reason, ?string $reference, ?string $notes, User $user): InventoryMovement
    {
        return $this->move($product, null, $locationId, $quantity, 'STOCK_IN', $reason, $reference, $notes, $user);
    }

    public function stockOut(Product $product, int $locationId, float $quantity, string $reason, ?string $reference, ?string $notes, User $user): InventoryMovement
    {
        return $this->move($product, $locationId, null, $quantity, 'STOCK_OUT', $reason, $reference, $notes, $user);
    }

    public function transfer(Product $product, int $fromId, int $toId, float $quantity, string $reason, ?string $reference, ?string $notes, User $user): InventoryMovement
    {
        if ($fromId === $toId) {
            throw ValidationException::withMessages(['destination_location_id' => 'Choose a different destination location.']);
        }

return $this->move($product, $fromId, $toId, $quantity, 'TRANSFER', $reason, $reference, $notes, $user);
    }

    public function adjust(Product $product, int $locationId, float $quantity, string $reason, ?string $reference, ?string $notes, User $user): InventoryMovement
    {
        return $this->move($product, $quantity < 0 ? $locationId : null, $quantity > 0 ? $locationId : null, abs($quantity), 'ADJUSTMENT', $reason, $reference, $notes, $user, $quantity);
    }

    private function move(Product $product, ?int $sourceId, ?int $destinationId, float $quantity, string $type, string $reason, ?string $reference, ?string $notes, User $user, ?float $signedAdjustment = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
        }

        return DB::transaction(function () use ($product, $sourceId, $destinationId, $quantity, $type, $reason, $reference, $notes, $user, $signedAdjustment) {
            $source = $sourceId ? Inventory::query()->where(['product_id' => $product->id, 'location_id' => $sourceId])->lockForUpdate()->first() : null;
            if ($source && $source->available() < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient available stock at the selected location.']);
            }
            if ($source) {
                $source->decrement('quantity', $quantity);
            }
            $destination = null;
            if ($destinationId) {
                $destination = Inventory::query()->lockForUpdate()->firstOrCreate(['product_id' => $product->id, 'location_id' => $destinationId], ['quantity' => 0, 'reserved_quantity' => 0]);
                if ($type === 'ADJUSTMENT' && $signedAdjustment < 0) {
                    $destination->decrement('quantity', $quantity);
                } elseif (! ($type === 'ADJUSTMENT' && $signedAdjustment < 0)) {
                    $destination->increment('quantity', $quantity);
                }
            }
            $balance = $destination?->fresh()->quantity ?? $source?->fresh()->quantity;
            $movement = InventoryMovement::create(['product_id' => $product->id, 'source_location_id' => $sourceId, 'destination_location_id' => $destinationId, 'type' => $type, 'quantity' => $quantity, 'balance_after' => $balance, 'reference' => $reference, 'reason' => $reason, 'notes' => $notes, 'performed_by' => $user->id]);
            $this->audit->record($type, 'Inventory', "{$type} of {$quantity} {$product->unit} for {$product->code}", $movement, [], ['quantity' => $quantity, 'balance_after' => $balance]);

            return $movement;
        });
    }
}
