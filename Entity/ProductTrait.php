<?php

namespace Plugin\ShoppingMall\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

/**
 * @Eccube\EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
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
     * @var string
     *
     * @ORM\Column(name="external_sales_url", type="string", length=1024, nullable=true)
     */
    private $external_sales_url;

    /**
     * @var boolean
     *
     * @ORM\Column(name="should_show_price", type="boolean", options={"default":true})
     */
    private $should_show_price = true;

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

    /**
     * @return string
     */
    public function getExternalSalesUrl()
    {
        return $this->external_sales_url;
    }

    /**
     * @param string $externalSalesUrl
     */
    public function setExternalSalesUrl($externalSalesUrl)
    {
        $this->external_sales_url = $externalSalesUrl;
    }

    /**
     * @return boolean
     */
    public function getShouldShowPrice()
    {
        return $this->should_show_price;
    }

    /**
     * @param boolean $shouldShowPrice
     */
    public function setShouldShowPrice($shouldShowPrice)
    {
        $this->should_show_price = $shouldShowPrice;
    }
}
