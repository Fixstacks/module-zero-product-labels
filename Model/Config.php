<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\ProductLabels\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * This module's fields of Stores > Configuration > Zero > Theme > Product
 * Badges (zero_theme/badges), for the current store view.
 */
class Config
{
    private const PATH = 'zero_theme/badges/';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function showsCustom(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PATH . 'custom', ScopeInterface::SCOPE_STORE);
    }

    /** "Only a few left" at this salable quantity or below; 0 is off. */
    public function lowStockQty(): float
    {
        return max(0.0, (float) $this->scopeConfig->getValue(self::PATH . 'low_stock_qty', ScopeInterface::SCOPE_STORE));
    }

    /**
     * The order subtotal Free Shipping starts at, when the badge is on and the
     * Free Shipping method is active with a minimum; 0 otherwise.
     */
    public function freeShippingFrom(): float
    {
        if (!$this->scopeConfig->isSetFlag(self::PATH . 'free_shipping', ScopeInterface::SCOPE_STORE)
            || !$this->scopeConfig->isSetFlag('carriers/freeshipping/active', ScopeInterface::SCOPE_STORE)) {
            return 0.0;
        }

        return max(0.0, (float) $this->scopeConfig->getValue('carriers/freeshipping/free_shipping_subtotal', ScopeInterface::SCOPE_STORE));
    }
}
