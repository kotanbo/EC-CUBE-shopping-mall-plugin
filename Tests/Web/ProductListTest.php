<?php

namespace Plugin\ShoppingMall\Tests\Web;

use Eccube\Entity\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ProductListTest
 */
class ProductListTest extends ShopWebCommon
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
        $this->deleteAllRows([$this->entityManager->getClassMetadata(Product::class)->getTableName()]);

        $this->Product = $this->createProduct();
    }

    public function testProductListWithoutShopRelatedColumns()
    {
        $productId = $this->Product->getId();
        $this->Product->setExternalSalesUrl(null);
        $this->Product->setShouldShowPrice(true);
        $this->entityManager->persist($this->Product);
        $this->entityManager->flush();
        $crawler = $this->client->request('GET', $this->generateUrl('product_list'));
        $html = $crawler->filter('body')->html();
        $this->assertNotContains('販売サイトへ', $html);
        $url = $this->generateUrl('product_detail', ['id' => $productId], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertNotContains('$(\'a[href="'.$url.'"]\')', $html);
    }

    public function testProductListWithShopRelatedColumns()
    {
        $productId = $this->Product->getId();
        $this->Product->setExternalSalesUrl('https://www.example.com');
        $this->Product->setShouldShowPrice(false);
        $this->entityManager->persist($this->Product);
        $this->entityManager->flush();
        $crawler = $this->client->request('GET', $this->generateUrl('product_list'));
        $html = $crawler->filter('body')->html();
        $this->assertContains('販売サイトへ', $html);
        $url = $this->generateUrl('product_detail', ['id' => $productId], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertContains('$(\'a[href="'.$url.'"]\')', $html);
    }
}
