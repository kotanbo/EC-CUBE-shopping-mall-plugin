<?php

namespace Plugin\ShoppingMall\Tests\Web\Admin;

use Eccube\Common\Constant;
use Faker\Generator;
use Plugin\ShoppingMall\Entity\Shop;
use Plugin\ShoppingMall\Tests\Web\ShopWebCommon;
use Symfony\Component\DomCrawler\Crawler;
use Plugin\ShoppingMall\Repository\ShopRepository;

/**
 * Class ShopControllerTest.
 */
class ShopControllerTest extends ShopWebCommon
{
    /**
     * @var ShopRepository
     */
    protected $shopRepository;

    public function setUp()
    {
        parent::setUp();
        $this->deleteAllRows([$this->entityManager->getClassMetadata(Shop::class)->getTableName()]);

        $this->shopRepository = $this->container->get(ShopRepository::class);
    }

    public function testRender()
    {
        $crawler = $this->client->request('GET', $this->generateUrl('shopping_mall_admin_shop_index'));
        $this->assertEquals(0, $crawler->filter('.sortable-item')->count());
    }

    public function testList()
    {
        $numberTest = 100;
        for ($i = 1; $i <= $numberTest; ++$i) {
            $this->createShop($i);
        }

        $crawler = $this->client->request('GET', $this->generateUrl('shopping_mall_admin_shop_index'));
        $number = count($crawler->filter('.sortable-container .sortable-item'));

        $this->actual = $number;
        $this->expected = $numberTest;
        $this->verify();
    }

    public function testCreateNameIsEmpty()
    {
        $formData = $this->createFormData();
        $formData['name'] = '';
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('shopping_mall_admin_shop_new'),
            ['shop' => $formData]
        );

        $this->assertContains('入力されていません。', $crawler->filter('#form1 .form-error-message')->html());
    }

    public function testCreateNameIsDuplicate()
    {
        $Shop = $this->createShop(1);
        $formData = $this->createFormData();
        $formData['name'] = $Shop->getName();
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('shopping_mall_admin_shop_new'),
            ['shop' => $formData]
        );

        $this->assertContains('既に使用されています。', $crawler->filter('#form1 .form-error-message')->html());
    }

    public function testCreate()
    {
        $formData = $this->createFormData();
        $this->client->request(
            'POST',
            $this->generateUrl('shopping_mall_admin_shop_new'),
            ['shop' => $formData]
        );

        /** @var Shop $Shop */
        $Shop = $this->shopRepository->findOneBy([], ['id' => 'DESC']);

        // Check redirect
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('shopping_mall_admin_shop_edit', ['id' => $Shop->getId()])));

        /**
         * @var Crawler
         */
        $crawler = $this->client->followRedirect();
        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // check item name
        $name = $crawler->filter('#shop_name')->attr('value');
        $this->assertContains($formData['name'], $name);
    }

    public function testEditNameIsEmpty()
    {
        $Shop = $this->createShop(1);
        $formData = $this->createFormData();
        $formData['name'] = '';

        /**
         * @var Crawler
         */
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('shopping_mall_admin_shop_edit', ['id' => $Shop->getId()]),
            ['shop' => $formData]
        );

        $this->assertContains('入力されていません。', $crawler->filter('#form1 .form-error-message')->html());
    }

    public function testEditNameIsDuplicate()
    {
        $ShopBefore = $this->createShop(1);
        $Shop = $this->createShop(1);
        $formData = $this->createFormData();

        $formData['name'] = $ShopBefore->getName();

        /**
         * @var Crawler
         */
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('shopping_mall_admin_shop_edit', ['id' => $Shop->getId()]),
            ['shop' => $formData]
        );

        // Check message
        $this->assertContains('既に使用されています。', $crawler->filter('#form1 .form-error-message')->html());
    }

    public function testEdit()
    {
        $Shop = $this->createShop(1);
        $formData = $this->createFormData();

        $this->client->request(
            'POST',
            $this->generateUrl('shopping_mall_admin_shop_edit', ['id' => $Shop->getId()]),
            ['shop' => $formData]
        );

        // Check redirect
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('shopping_mall_admin_shop_edit', ['id' => $Shop->getId()])));

        $crawler = $this->client->followRedirect();
        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check item name
        $name = $crawler->filter('#shop_name')->attr('value');
        $this->assertContains($formData['name'], $name);
    }

    public function testDeleteGetMethod()
    {
        $Shop = $this->createShop();

        $this->client->request(
            'GET',
            $this->generateUrl('shopping_mall_admin_shop_delete', ['id' => $Shop->getId()])
        );

        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteIdIsNull()
    {
        $this->client->request(
            'DELETE',
            $this->generateUrl('shopping_mall_admin_shop_delete', ['id' => null])
        );

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteIdIsNotExist()
    {
        $id = 999;
        $this->client->request('DELETE', $this->generateUrl('shopping_mall_admin_shop_delete', ['id' => $id]));

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $Shop = $this->createShop();

        $this->client->request(
            'DELETE',
            $this->generateUrl('shopping_mall_admin_shop_delete', ['id' => $Shop->getId()])
        );
        // Check redirect
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('shopping_mall_admin_shop_index')));

        $crawler = $this->client->followRedirect();

        // Check message
        $this->assertContains('ショップを削除しました。', $crawler->filter('.alert')->html());

        // Check item name
        $this->assertEquals(0, $crawler->filter('.sortable-item')->count());

        $this->assertNull($Shop->getId());
    }

    public function testMoveRankTestIsNotPostAjax()
    {
        $Shop01 = $this->createShop(1);
        $oldSortNo = $Shop01->getSortNo();
        $Shop02 = $this->createShop(2);
        $newSortNo = $Shop02->getSortNo();

        $request = [
            $Shop01->getId() => $newSortNo,
            $Shop02->getId() => $oldSortNo,
        ];

        $this->client->request(
            'GET',
            $this->generateUrl('shopping_mall_admin_shop_move_sort_no'),
            $request,
            [],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->actual = $Shop01->getSortNo();
        $this->expected = $oldSortNo;
        $this->verify();

        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());
    }

    public function testMoveRank()
    {
        $Shop01 = $this->createShop(1);
        $oldSortNo = $Shop01->getSortNo();
        $Shop02 = $this->createShop(2);
        $newSortNo = $Shop02->getSortNo();

        $request = [
            $Shop01->getId() => $newSortNo,
            $Shop02->getId() => $oldSortNo,
        ];

        $this->client->request(
            'POST',
            $this->generateUrl('shopping_mall_admin_shop_move_sort_no'),
            $request,
            [],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->actual = $Shop01->getSortNo();
        $this->expected = $newSortNo;
        $this->verify();
    }

    /**
     * Create data form.
     *
     * @return array
     */
    private function createFormData()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        return [
            Constant::TOKEN_NAME => 'dummy',
            'name' => $faker->word,
        ];
    }
}
