<?php

namespace Plugin\ShoppingMall\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;

if (!class_exists('\Plugin\ShoppingMall\Entity\Shop')) {
    /**
     * Shop
     *
     * @ORM\Table(name="plg_shopping_mall_shop")
     * @ORM\Entity(repositoryClass="Plugin\ShoppingMall\Repository\ShopRepository")
     */
    class Shop extends AbstractEntity
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
         * @var string
         *
         * @ORM\Column(name="name", type="string", length=255)
         */
        private $name;

        /**
         * @var int
         *
         * @ORM\Column(name="sort_no", type="integer")
         */
        private $sort_no;

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
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * @return string
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * @param string $name
         *
         * @return $this
         */
        public function setName($name)
        {
            $this->name = $name;

            return $this;
        }

        /**
         * @return int
         */
        public function getSortNo()
        {
            return $this->sort_no;
        }

        /**
         * @param int $sortNo
         *
         * @return $this
         */
        public function setSortNo($sortNo)
        {
            $this->sort_no = $sortNo;

            return $this;
        }

        /**
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
         * @return \DateTime
         */
        public function getCreateDate()
        {
            return $this->create_date;
        }

        /**
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
         * @return \DateTime
         */
        public function getUpdateDate()
        {
            return $this->update_date;
        }

        /**
         * Unique check.
         *
         * @param ClassMetadata $metadata
         */
        public static function loadValidatorMetadata(ClassMetadata $metadata)
        {
            $metadata->addConstraint(new UniqueEntity([
                'fields' => 'name',
            ]));
        }
    }
}
