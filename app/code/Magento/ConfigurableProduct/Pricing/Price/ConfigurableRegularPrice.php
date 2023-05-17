<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ConfigurableProduct\Pricing\Price;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Framework\Pricing\Price\AbstractPrice;

/**
 * Class RegularPrice
 */
class ConfigurableRegularPrice extends AbstractPrice implements
    ConfigurableRegularPriceInterface,
    ResetAfterRequestInterface
{
    /**
     * Price type
     */
    public const PRICE_CODE = 'regular_price';

    /**
     * @var \Magento\Framework\Pricing\Amount\AmountInterface
     */
    protected $maxRegularAmount;

    /**
     * @var \Magento\Framework\Pricing\Amount\AmountInterface
     */
    protected $minRegularAmount;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var \Magento\ConfigurableProduct\Pricing\Price\PriceResolverInterface
     */
    protected $priceResolver;

    /**
     * @var ConfigurableOptionsProviderInterface
     */
    private $configurableOptionsProvider;

    /**
     * @var LowestPriceOptionsProviderInterface
     */
    private $lowestPriceOptionsProvider;

    /**
     * @param \Magento\Framework\Pricing\SaleableInterface $saleableItem
     * @param float $quantity
     * @param \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param PriceResolverInterface $priceResolver
     * @param LowestPriceOptionsProviderInterface $lowestPriceOptionsProvider
     */
    public function __construct(
        \Magento\Framework\Pricing\SaleableInterface $saleableItem,
        $quantity,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        PriceResolverInterface $priceResolver,
        LowestPriceOptionsProviderInterface $lowestPriceOptionsProvider = null
    ) {
        parent::__construct($saleableItem, $quantity, $calculator, $priceCurrency);
        $this->priceResolver = $priceResolver;
        $this->lowestPriceOptionsProvider = $lowestPriceOptionsProvider ?:
            ObjectManager::getInstance()->get(LowestPriceOptionsProviderInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function getValue()
    {
        if (!isset($this->values[$this->product->getId()])) {
            $this->values[$this->product->getId()] = $this->priceResolver->resolvePrice($this->product);
        }

        return $this->values[$this->product->getId()];
    }

    /**
     * Checks if all children simple products have the same price.
     *
     * @return bool Returns true if all products have the same price, false otherwise.
     */
    public function checkProductsHaveEqualPrices():bool
    {
        $minPrice = $this->getMinRegularAmount()->getValue();
        $maxPrice= $this->getMaxRegularAmount()->getValue();
        foreach ($this->getUsedProducts() as $subProduct) {
            if ($subProduct->isAvailable() && !$subProduct->isDisabled()) {
                if ($specialPrice=$subProduct->getSpecialPrice()) {
                    if ($specialPrice!=$minPrice || $subProduct->getFinalPrice()!=$minPrice) {
                        return false;
                    } else {
                        return true;
                    }
                }
            }
        }
        return  $minPrice === $maxPrice;
    }

    /**
     * @inheritdoc
     */
    public function getAmount()
    {
        return $this->getMinRegularAmount();
    }

    /**
     * @inheritdoc
     */
    public function getMaxRegularAmount()
    {
        if (null === $this->maxRegularAmount) {
            $this->maxRegularAmount = $this->doGetMaxRegularAmount() ?: false;
        }
        return $this->maxRegularAmount;
    }

    /**
     * Get max regular amount
     *
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    protected function doGetMaxRegularAmount()
    {
        $maxAmount = null;
        foreach ($this->getUsedProducts() as $product) {
            $childPriceAmount = $product->getPriceInfo()->getPrice(self::PRICE_CODE)->getAmount();
            if (!$maxAmount || ($childPriceAmount->getValue() > $maxAmount->getValue())) {
                $maxAmount = $childPriceAmount;
            }
        }
        return $maxAmount;
    }

    /**
     * @inheritdoc
     */
    public function getMinRegularAmount()
    {
        if (null === $this->minRegularAmount) {
            $this->minRegularAmount = $this->doGetMinRegularAmount() ?: parent::getAmount();
        }
        return $this->minRegularAmount;
    }

    /**
     * Get min regular amount
     *
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    protected function doGetMinRegularAmount()
    {
        $minAmount = null;
        foreach ($this->lowestPriceOptionsProvider->getProducts($this->product) as $product) {
            $childPriceAmount = $product->getPriceInfo()->getPrice(self::PRICE_CODE)->getAmount();
            if (!$minAmount || ($childPriceAmount->getValue() < $minAmount->getValue())) {
                $minAmount = $childPriceAmount;
            }
        }
        return $minAmount;
    }

    /**
     * Get children simple products
     *
     * @return Product[]
     */
    protected function getUsedProducts()
    {
        return $this->getConfigurableOptionsProvider()->getProducts($this->product);
    }

    /**
     * Retrieve Configurable Option Provider
     *
     * @return \Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface
     * @deprecated 100.1.1
     * @see we don't recommend this approach anymore
     */
    private function getConfigurableOptionsProvider()
    {
        if (null === $this->configurableOptionsProvider) {
            $this->configurableOptionsProvider = ObjectManager::getInstance()
                ->get(ConfigurableOptionsProviderInterface::class);
        }
        return $this->configurableOptionsProvider;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->values = [];
    }
}
