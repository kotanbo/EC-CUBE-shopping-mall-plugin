<?php

namespace Plugin\ShoppingMall\Tests\Web\Admin;

use Faker\Generator;
use Plugin\ShoppingMall\Tests\Web\ShopWebCommon;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Client;

/**
 * Class ShoppingMallConfigControllerTest.
 */
class ShoppingMallConfigControllerTest extends ShopWebCommon
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * Setup method.
     */
    public function setUp()
    {
        parent::setUp();
        $this->faker = $this->getFaker();
    }

    /**
     * Config routing.
     */
    public function testRouting()
    {
        /**
         * @var Client
         */
        $client = $this->client;
        /**
         * @var Crawler
         */
        $crawler = $this->client->request('GET', $this->generateUrl('shopping_mall_admin_config'));

        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertContains('商品の外部販売サイトURL設定', $crawler->html());
    }

    /**
     * Config submit.
     */
    public function testSuccess()
    {
        /**
         * @var Client
         */
        $client = $this->client;
        /**
         * @var Crawler
         */
        $crawler = $this->client->request('GET', $this->generateUrl('shopping_mall_admin_config'));

        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $crawler->selectButton('登録')->form();

        $form['shopping_mall_config[needs_external_sales_url]'] = 1;
        $crawler = $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirection($this->generateUrl('shopping_mall_admin_config')));

        $crawler = $client->followRedirect();
        $this->assertContains('登録しました。', $crawler->html());
    }
}
