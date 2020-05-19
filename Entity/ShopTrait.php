<?php

namespace Plugin\ShoppingMall\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

/**
 * @Eccube\EntityExtension("Plugin\ShoppingMall\Entity\Shop")
 */
trait ShopTrait
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="order_email", type="string", length=255, nullable=true)
     */
    private $order_email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="memo", type="string", length=4000, nullable=true)
     */
    private $memo;

    /**
     * @var \Eccube\Entity\Master\SaleType|null
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\SaleType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sale_type_id", referencedColumnName="id")
     * })
     */
    private $SaleType;

    /**
     * @return string|null
     */
    public function getOrderEmail()
    {
        return $this->order_email;
    }

    /**
     * @param string|null $orderEmail
     *
     * @return $this
     */
    public function setOrderEmail($orderEmail)
    {
        $this->order_email = $orderEmail;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * @param string|null $memo
     *
     * @return $this
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * @param \Eccube\Entity\Master\SaleType|null $SaleType
     *
     * @return $this
     */
    public function setSaleType(\Eccube\Entity\Master\SaleType $SaleType)
    {
        $this->SaleType = $SaleType;

        return $this;
    }

    /**
     * @return \Eccube\Entity\Master\SaleType|null
     */
    public function getSaleType()
    {
        return $this->SaleType;
    }

    /**
     * @return bool
     */
    public function isSaleType()
    {
        return !is_null($this->SaleType);
    }
}
