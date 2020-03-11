<?php

namespace Plugin\ShoppingMall\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\ShoppingMall\Entity\Shop;

/**
 * Class ShopWebCommon
 */
class ShopWebCommon extends AbstractAdminWebTestCase
{

    protected function createProductFormData()
    {
        return [
            'class' => [
                'sale_type' => 1,
                'price01' => 101,
                'price02' => 102,
                'stock' => 100,
                'stock_unlimited' => 0,
                'code' => 'test-code',
                'sale_limit' => null,
                'delivery_duration' => '',
            ],
            'name' => 'test name',
            'product_image' => [],
            'description_detail' => 'test description detail',
            'description_list' => 'test description list',
            'Category' => 1,
            'Tag' => 1,
            'search_word' => 'test search word',
            'free_area' => 'test free area',
            'Status' => 1,
            'note' => 'test note',
            'tags' => null,
            'images' => null,
            'add_images' => null,
            'delete_images' => null,
            Constant::TOKEN_NAME => 'dummy',
        ];
    }

    protected function createMemberFormData()
    {
        $form = [
            'Work' => 1,
            'Authority' => 1,
            'name' => 'test name',
            'department' => 'test department',
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
        $Shop = new Shop();
        $Shop->setName('test shop');
        $Shop->setSortNo(is_null($sortNo) ? 1 : $sortNo);

        $this->entityManager->persist($Shop);
        $this->entityManager->flush();

        return $Shop;
    }
}
