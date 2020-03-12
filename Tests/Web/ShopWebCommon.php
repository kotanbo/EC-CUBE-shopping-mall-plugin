<?php

namespace Plugin\ShoppingMall\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Faker\Generator;
use Plugin\ShoppingMall\Entity\Shop;

/**
 * Class ShopWebCommon
 */
class ShopWebCommon extends AbstractAdminWebTestCase
{

    protected function createProductFormData()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        $price01 = $faker->randomNumber(5);
        if (mt_rand(0, 1)) {
            $price01 = number_format($price01);
        }

        $price02 = $faker->randomNumber(5);
        if (mt_rand(0, 1)) {
            $price02 = number_format($price02);
        }

        return [
            'class' => [
                'sale_type' => 1,
                'price01' => $price01,
                'price02' => $price02,
                'stock' => $faker->randomNumber(3),
                'stock_unlimited' => 0,
                'code' => $faker->word,
                'sale_limit' => null,
                'delivery_duration' => '',
            ],
            'name' => $faker->word,
            'product_image' => [],
            'description_detail' => $faker->realText,
            'description_list' => $faker->paragraph,
            'Category' => 1,
            'Tag' => 1,
            'search_word' => $faker->word,
            'free_area' => $faker->realText,
            'Status' => 1,
            'note' => $faker->realText,
            'tags' => null,
            'images' => null,
            'add_images' => null,
            'delete_images' => null,
            Constant::TOKEN_NAME => 'dummy',
        ];
    }

    protected function createMemberFormData()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        $form = [
            'Work' => 1,
            'Authority' => 1,
            'name' => $faker->word,
            'department' => $faker->word,
            'login_id' => 'test_login_id',
            'password' => [
                'first' => 'test_password',
                'second' => 'test_password',
            ],
            Constant::TOKEN_NAME => 'dummy',
        ];

        return $form;
    }

    /**
     * Create maker
     *
     * @param int $sortNo
     *
     * @return Shop
     */
    protected function createShop($sortNo = null)
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        if (!$sortNo) {
            $sortNo = $faker->randomNumber(3);
        }

        $Shop = new Shop();
        $Shop->setName($faker->word);
        $Shop->setSortNo($sortNo);

        $this->entityManager->persist($Shop);
        $this->entityManager->flush();

        return $Shop;
    }
}
