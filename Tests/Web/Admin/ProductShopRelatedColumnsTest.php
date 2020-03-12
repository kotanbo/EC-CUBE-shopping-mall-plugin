<?php

namespace Plugin\ShoppingMall\Tests\Web\Admin;

use Eccube\Entity\Product;
use Eccube\Entity\ProductCategory;
use Eccube\Entity\ProductClass;
use Eccube\Entity\ProductImage;
use Eccube\Entity\ProductStock;
use Eccube\Entity\ProductTag;
use Faker\Generator;
use Plugin\ShoppingMall\Tests\Web\ShopWebCommon;
use Symfony\Component\HttpKernel\Client;
use Eccube\Repository\ProductRepository;

/**
 * Class ProductShopRelatedColumnsTest.
 */
class ProductShopRelatedColumnsTest extends ShopWebCommon
{
    const EXTERNAL_SALES_URL = 'external_sales_url';
    const SHOULD_SHOW_PRICE = 'should_show_price';

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    public function setUp()
    {
        parent::setUp();
        // 外部キー設定を考慮して削除
        $this->deleteAllRows([$this->entityManager->getClassMetadata(ProductCategory::class)->getTableName()]);
        $this->deleteAllRows([$this->entityManager->getClassMetadata(ProductImage::class)->getTableName()]);
        $this->deleteAllRows([$this->entityManager->getClassMetadata(ProductStock::class)->getTableName()]);
        $this->deleteAllRows([$this->entityManager->getClassMetadata(ProductTag::class)->getTableName()]);
        $this->deleteAllRows([$this->entityManager->getClassMetadata(ProductClass::class)->getTableName()]);
        $this->deleteAllRows([$this->entityManager->getClassMetadata(Product::class)->getTableName()]);

        $this->productRepository = $this->container->get(ProductRepository::class);
    }

    public function testProductNewRender()
    {
        $crawler = $this->client->request('GET', $this->generateUrl('admin_product_product_new'));
        $this->assertContains('外部販売サイト', $crawler->filter('body')->html());
        $this->assertContains('価格を表示', $crawler->filter('body')->html());
    }

    public function testProductNewWithAddExternalSalesUrlInvalid()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $formData = $this->createProductFormData();
        $formData[self::EXTERNAL_SALES_URL] = $faker->word;

        /**
         * @var Client
         */
        $client = $this->client;
        $crawler = $client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );

        // Check message
        $this->assertContains('有効なURLではありません。', $crawler->filter('.form-error-message')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);
        $this->actual = $Product;
        $this->expected = null;
        $this->verify();
    }

    public function testProductNewWithAddShopRelatedColumns()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $formData = $this->createProductFormData();
        $formData[self::EXTERNAL_SALES_URL] = $faker->url;
        $formData[self::SHOULD_SHOW_PRICE] = 1;

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );

        $arrTmp = explode('/', $client->getResponse()->getTargetUrl());
        $productId = $arrTmp[count($arrTmp) - 2];

        $this->assertTrue($client->getResponse()->isRedirection());
        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Product->getExternalSalesUrl();
        $this->expected = $formData[self::EXTERNAL_SALES_URL];
        $this->verify();

        $this->actual = $Product->getShouldShowPrice();
        $this->expected = $formData[self::SHOULD_SHOW_PRICE];
        $this->verify();
    }

    public function testProductEditRender()
    {
        $Product = $this->createProduct();

        $crawler = $this->client->request('GET', $this->generateUrl('admin_product_product_edit', ['id' => $Product->getId()]));
        $this->assertContains('外部販売サイト', $crawler->filter('body')->html());
        $this->assertContains('価格を表示', $crawler->filter('body')->html());
    }

    public function testProductEditWithAddExternalSalesUrlInvalid()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        // New product
        $formData = $this->createProductFormData();
        $formData[self::EXTERNAL_SALES_URL] = '';

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );

        $arrTmp = explode('/', $client->getResponse()->getTargetUrl());
        $productId = $arrTmp[count($arrTmp) - 2];

        $client->followRedirect();

        // Edit product test
        $formData = $this->createProductFormData();
        $formData[self::EXTERNAL_SALES_URL] = $faker->word;

        /**
         * @var Client
         */
        $client = $this->client;
        $crawler = $client->request(
            'POST',
            $this->generateUrl('admin_product_product_edit', ['id' => $productId]),
            ['admin_product' => $formData]
        );

        // Check message
        $this->assertContains('有効なURLではありません。', $crawler->filter('.form-error-message')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Product->getShop();
        $this->expected = null;
        $this->verify();
    }

    public function testProductEditWithAddShopRelatedColumns()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        // New product
        $formData = $this->createProductFormData();
        $formData[self::EXTERNAL_SALES_URL] = '';
        $formData[self::SHOULD_SHOW_PRICE] = '';

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );

        $arrTmp = explode('/', $client->getResponse()->getTargetUrl());
        $productId = $arrTmp[count($arrTmp) - 2];

        $client->followRedirect();

        // Edit product test
        $formData = $this->createProductFormData();
        $formData[self::EXTERNAL_SALES_URL] = $faker->url;
        $formData[self::SHOULD_SHOW_PRICE] = 1;

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_product_product_edit', ['id' => $productId]),
            ['admin_product' => $formData]
        );

        $this->assertTrue($client->getResponse()->isRedirection());
        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Product->getExternalSalesUrl();
        $this->expected = $formData[self::EXTERNAL_SALES_URL];
        $this->verify();

        $this->actual = $Product->getShouldShowPrice();
        $this->expected = $formData[self::SHOULD_SHOW_PRICE];
        $this->verify();
    }
}
