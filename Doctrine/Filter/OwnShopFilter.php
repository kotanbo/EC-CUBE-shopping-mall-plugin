<?php

namespace Plugin\ShoppingMall\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Eccube\Entity\ClassCategory;
use Eccube\Entity\ClassName;
use Eccube\Entity\Delivery;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Order;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\ShoppingMall\Entity\Shop;

class OwnShopFilter extends SQLFilter
{
    private $shopId = null;
    private $saleTypeId = null;

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!is_null($this->shopId)) {
            if (in_array(
                $targetEntity->reflClass->getName(),
                [Product::class, ProductClass::class, ClassCategory::class, ClassName::class, Order::class, Delivery::class]
            )) {
                return $targetTableAlias.'.shop_id = '.$this->getParameter('shop_id');
            }
        }
        if (!is_null($this->saleTypeId)) {
            if (in_array(
                $targetEntity->reflClass->getName(),
                [SaleType::class]
            )) {
                return $targetTableAlias.'.id = '.$this->getParameter('sale_type_id');
            }
        }

        return '';
    }

    public function setShopId(Shop $shop)
    {
        $this->shopId = $shop->getId();
        $this->setParameter('shop_id', $this->shopId);

        $SaleType = $shop->getSaleType();
        if ($SaleType) {
            $this->saleTypeId = $SaleType->getId();
            $this->setParameter('sale_type_id', $this->saleTypeId);
        }
    }
}
