<?php

namespace App\Features\Ai\DTO;

final readonly class GeneratedStockItem
{
    public function __construct(
        public string $name,
        public ?string $sku = null,
        public ?string $description = null,
        public int $quantity = 0,
        public string $unit = 'pcs',
        public ?float $price = null,
        public int $minStock = 5,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) ($data['name'] ?? '')),
            sku: filled($data['sku'] ?? null) ? (string) $data['sku'] : null,
            description: filled($data['description'] ?? null) ? (string) $data['description'] : null,
            quantity: max(0, (int) ($data['quantity'] ?? 0)),
            unit: filled($data['unit'] ?? null) ? (string) $data['unit'] : 'pcs',
            price: is_numeric($data['price'] ?? null) ? max(0.0, (float) $data['price']) : null,
            minStock: max(0, (int) ($data['min_stock'] ?? 5)),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'min_stock' => $this->minStock,
        ];
    }
}
