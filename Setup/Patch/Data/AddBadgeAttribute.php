<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\ProductLabels\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Zero\ProductLabels\Model\Badges;

/**
 * The "Badge" product attribute: a store's own badge for a product
 * ("Bestseller", "Limited", "Vegan"), per store view, loaded with product
 * lists; in every attribute set's General / Product Details group. Kept
 * when the module is disabled or removed (product data, as any attribute).
 * Before Zero_ProductLabels it came with Zero_Core: then nothing to add.
 */
class AddBadgeAttribute implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    public function apply(): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        if ($eavSetup->getAttributeId(Product::ENTITY, Badges::ATTRIBUTE)) {
            return;
        }
        $eavSetup->addAttribute(Product::ENTITY, Badges::ATTRIBUTE, [
            'type' => 'varchar',
            'label' => 'Badge',
            'input' => 'text',
            'global' => Attribute::SCOPE_STORE,
            'required' => false,
            'user_defined' => true,
            'visible' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => true,
            'note' => 'A short badge on the product\'s photo and page, e.g. Bestseller, Limited, Vegan. Empty: none.',
        ]);
        foreach ($eavSetup->getAllAttributeSetIds(Product::ENTITY) as $setId) {
            $group = $eavSetup->getAttributeGroup(Product::ENTITY, $setId, 'General')
                ?: $eavSetup->getAttributeGroup(Product::ENTITY, $setId, 'Product Details');
            if ($group) {
                $eavSetup->addAttributeToGroup(Product::ENTITY, $setId, (int) $group['attribute_group_id'], Badges::ATTRIBUTE, 96);
            }
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
