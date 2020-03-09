<?php

namespace Plugin\ShoppingMall\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Plugin\ShoppingMall\Entity\Shop;

class OwnShopProductFilter extends SQLFilter
{
    private $shopId = null;

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (is_null($this->shopId)) {
            return '';
        }
        if ($targetEntity->reflClass->getName() !== 'Eccube\Entity\Product') {
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
