<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\ProductLabels\Model;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * How many of a simple or virtual product can still be sold here: MSI's
 * salable quantity for the website's stock (the source quantity less open
 * orders' reservations), or without MSI the stock item's quantity. Null
 * when it isn't counted (stock not managed, out of stock, a configurable).
 */
class LowStock
{
    private const COUNTED_TYPES = ['simple', 'virtual'];
    private const SALABLE_QTY = 'Magento\InventorySalesApi\Api\GetProductSalableQtyInterface';
    private const STOCK_RESOLVER = 'Magento\InventorySalesApi\Api\StockResolverInterface';

    /** @var array<string, float|null> */
    private array $loaded = [];

    private ?int $stockId = null;

    public function __construct(
        private readonly StockRegistryInterface $stockRegistry,
        private readonly StoreManagerInterface $storeManager,
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    public function qty(Product $product): ?float
    {
        $sku = (string) $product->getSku();
        if ($sku === '' || !in_array($product->getTypeId(), self::COUNTED_TYPES, true)) {
            return null;
        }
        if (!array_key_exists($sku, $this->loaded)) {
            $this->loaded[$sku] = $this->load($product);
        }

        return $this->loaded[$sku];
    }

    private function load(Product $product): ?float
    {
        try {
            $item = $this->stockRegistry->getStockItem((int) $product->getId(), (int) $this->storeManager->getWebsite()->getId());
            if (!$item->getManageStock() || !$item->getIsInStock()) {
                return null;
            }
            // Asked for here, not injected: MSI may be off (the class then doesn't exist).
            if (interface_exists(self::SALABLE_QTY) && interface_exists(self::STOCK_RESOLVER)) {
                return (float) $this->objectManager->get(self::SALABLE_QTY)->execute((string) $product->getSku(), $this->stockId());
            }

            return (float) $item->getQty();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function stockId(): int
    {
        if ($this->stockId === null) {
            $website = $this->storeManager->getWebsite();
            $this->stockId = (int) $this->objectManager->get(self::STOCK_RESOLVER)
                ->execute('website', (string) $website->getCode())->getStockId();
        }

        return $this->stockId;
    }
}
