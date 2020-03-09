<?php

namespace Plugin\ShoppingMall\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Eccube\Entity\ClassCategory;
use Eccube\Entity\ClassName;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\ShoppingMall\Entity\Shop;

class OwnShopFilter extends SQLFilter
{
    private $shopId = null;

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (is_null($this->shopId)) {
            return '';
        }
        if (!in_array(
            $targetEntity->reflClass->getName(),
            [Product::class, ProductClass::class, ClassCategory::class, ClassName::class]
        )) {
            return '';
        }

        return $targetTableAlias.'.shop_id = '.$this->getParameter('shop_id');
    }

    public function setShopId(Shop $shop)
    {
        $this->shopId = $shop->getId();
        $this->setParameter('shop_id', $this->shopId);
    }
}
