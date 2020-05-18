<?php

namespace Plugin\ShoppingMall\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

/**
 * @Eccube\EntityExtension("Eccube\Entity\ClassCategory")
 */
trait ClassCategoryTrait
{
    /**
     * @var Shop|null
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ShoppingMall\Entity\Shop")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="shop_id", referencedColumnName="id")
     * })
     */
    private $Shop;

    /**
     * @return Shop|null
     */
    public function getShop()
    {
        return $this->Shop;
    }

    /**
     * @param Shop|null $Shop
     *
     * @return $this
     */
    public function setShop(Shop $Shop = null)
    {
        $this->Shop = $Shop;

        return $this;
    }
}
