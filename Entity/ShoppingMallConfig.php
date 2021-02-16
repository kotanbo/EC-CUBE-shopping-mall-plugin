<?php

namespace Plugin\ShoppingMall\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Master\CsvType;

/**
 * ShoppingMallConfig
 *
 * @ORM\Table(name="plg_shopping_mall_config")
 * @ORM\Entity(repositoryClass="Plugin\ShoppingMall\Repository\ShoppingMallConfigRepository")
 */
class ShoppingMallConfig extends AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="needs_external_sales_url", type="boolean", options={"default":true})
     */
    private $needs_external_sales_url;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * Set shopping mall config id.
     *
     * @param string $id
     *
     * @return ShoppingMallConfig
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set needs_external_sales_url.
     *
     * @param boolean $needsExternalSalesUrl
     *
     * @return ShoppingMallConfig
     */
    public function setNeedsExternalSalesUrl($needsExternalSalesUrl)
    {
        $this->needs_external_sales_url = $needsExternalSalesUrl;

        return $this;
    }

    /**
     * Get needs_external_sales_url.
     *
     * @return boolean
     */
    public function getNeedsExternalSalesUrl()
    {
        return $this->needs_external_sales_url;
    }

    /**
     * Set create_date.
     *
     * @param \DateTime $createDate
     *
     * @return $this
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date.
     *
     * @param \DateTime $updateDate
     *
     * @return $this
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
