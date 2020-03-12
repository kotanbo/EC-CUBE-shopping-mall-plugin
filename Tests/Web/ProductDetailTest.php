<?php

namespace Plugin\ShoppingMall\Tests\Web;

use Eccube\Entity\Product;

/**
 * Class ProductDetailTest
 */
class ProductDetailTest extends ShopWebCommon
{
    /**
     * @var Product
     */
    protected $Product;

    /**
     * Set up function.
     */
    public function setUp()
    {
        parent::setUp();

        $this->Product = $this->createProduct();
    }

    public function testProductDetailWithoutShopRelatedColumns()
    {
        $productId = $this->Product->getId();
        $this->Product->setExternalSalesUrl(null);
        $this->Product->setShouldShowPrice(true);
        $this->entityManager->persist($this->Product);
        $this->entityManager->flush();
        $crawler = $this->client->request('GET', $this->generateUrl('product_detail', ['id' => $productId]));
        $html = $crawler->filter('body')->html();
        $this->assertNotContains('販売サイトへ', $html);
        $this->assertNotContains("$('.ec-productRole__priceRegular').remove()", $html);
    }

    public function testProductDetailWithShopRelatedColumns()
    {
        $productId = $this->Product->getId();
        $this->Product->setExternalSalesUrl('https://www.example.com');
        $this->Product->setShouldShowPrice(false);
        $this->entityManager->persist($this->Product);
        $this->entityManager->flush();
        $crawler = $this->client->request('GET', $this->generateUrl('product_detail', ['id' => $productId]));
        $html = $crawler->filter('#external_sales_url')->html();
        $this->assertContains('販売サイトへ', $html);
        $html = $crawler->filter('body')->html();
        $this->assertContains("$('.ec-productRole__priceRegular').remove()", $html);
    }
}
