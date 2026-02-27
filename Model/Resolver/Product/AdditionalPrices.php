<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types = 1);

namespace MageWorx\GiftCardsGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Resolver that exposes fixed gift-card amounts (`mageworx_gc_additional_price`)
 * in the *current display currency*.
 *
 * – `value`  : float  — converted numeric amount (no formatting)
 * – `label`  : string — the same amount formatted for UI (currency symbol etc.)
 */
class AdditionalPrices implements ResolverInterface
{
    /**
     * Converts prices between store/base and display currencies.
     *
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(PriceCurrencyInterface $priceCurrency)
    {
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field       $field,
                    $context,
        ResolveInfo $info,
        ?array      $value = null,
        ?array      $args = null
    ): Value|array|null {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('The "model" value must be specified.'));
        }

        /** @var Product $product */
        $product = $value['model'];

        // Resolver is relevant only for MageWorx Gift-Card products
        if (
            $product->getTypeId() !== \MageWorx\GiftCards\Model\Product\Type\GiftCards::TYPE_CODE
            || !$product->getData('mageworx_gc_additional_price')
        ) {
            return null;
        }

        $result = [];

        foreach (explode(';', (string)$product->getData('mageworx_gc_additional_price')) as $rawPrice) {
            $rawPrice  = (float)$rawPrice;                          // value in *base* currency
            $converted = $this->priceCurrency->convert($rawPrice);  // display-currency value

            $result[] = [
                'value' => $converted,                              // numeric, converted
                'label' => $this->priceCurrency->format(
                    $converted,                                     // human-readable
                    false                          // do not add <span> container
                )
            ];
        }

        return $result ?: null;
    }
}