<?php

namespace Plugin\ShoppingMall;

use Eccube\Common\EccubeNav;

class ShoppingMallNav implements EccubeNav
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getNav()
    {
        return [
            'setting' => [
                'children' => [
                    'shopping_mall.shop' => [
                        'id' => 'shopping_mall.shop',
                        'name' => 'shopping_mall.shop.admin.title',
                        'url' => 'shopping_mall_shop_admin_index',
                    ],
                ],
            ],
        ];
    }
}
