<?php

namespace Plugin\ShoppingMall\Tests\Web\Admin;

use Plugin\ShoppingMall\Entity\Shop;
use Plugin\ShoppingMall\Tests\Web\ShopWebCommon;
use Symfony\Component\HttpKernel\Client;
use Eccube\Repository\MemberRepository;

/**
 * Class MemberShopTest.
 */
class MemberShopTest extends ShopWebCommon
{
    const SHOP = 'Shop';

    /**
     * @var MemberRepository
     */
    protected $memberRepository;

    public function setUp()
    {
        parent::setUp();
        $this->deleteAllRows([$this->entityManager->getClassMetadata(Shop::class)->getTableName()]);

        $this->memberRepository = $this->container->get(MemberRepository::class);
    }

    public function testMemberNewRender()
    {
        $crawler = $this->client->request('GET', $this->generateUrl('admin_setting_system_member_new'));
        $this->assertContains('ショップ', $crawler->filter('body .c-container')->html());
    }

    public function testMemberNewWithoutShop()
    {
        $crawler = $this->client->request('GET', $this->generateUrl('admin_setting_system_member_new'));
        $this->assertContains('ショップ', $crawler->filter('body .c-container')->html());
    }

    public function testMemberNewWithShopWithoutShopSelect()
    {
        $Shop = $this->createShop();

        $crawler = $this->client->request('GET', $this->generateUrl('admin_setting_system_member_new'));
        $this->assertContains($Shop->getName(), $crawler->filter('body .c-container')->html());
    }

    public function testMemberNewWithAddShopInvalid()
    {
        $Shop = $this->createShop();
        $formData = $this->createMemberFormData();
        $formData[self::SHOP] = $Shop->getId() + 1;

        /**
         * @var Client
         */
        $client = $this->client;
        $crawler = $client->request(
            'POST',
            $this->generateUrl('admin_setting_system_member_new'),
            ['admin_member' => $formData]
        );

        // Check message
        $this->assertContains('有効な値ではありません。', $crawler->filter('.form-error-message')->html());

        // Check database
        $Member = $this->memberRepository->findOneBy([], ['id' => 'DESC']);
        $this->actual = $Member->getShop();
        $this->expected = null;
        $this->verify();
    }

    public function testMemberNewWithAddShop()
    {
        $Shop = $this->createShop();
        $formData = $this->createMemberFormData();
        $formData[self::SHOP] = $Shop->getId();

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_setting_system_member_new'),
            ['admin_member' => $formData]
        );

        $arrTmp = explode('/', $client->getResponse()->getTargetUrl());
        $memberId = $arrTmp[count($arrTmp) - 2];

        $this->assertTrue($client->getResponse()->isRedirection());
        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check database
        $Member = $this->memberRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Member->getShop()->getId();
        $this->expected = $formData[self::SHOP];
        $this->verify();
    }

    public function testMemberEditRender()
    {
        $Member = $this->createMember();

        $crawler = $this->client->request('GET', $this->generateUrl('admin_setting_system_member_edit', ['id' => $Member->getId()]));
        $this->assertContains('ショップ', $crawler->filter('body .c-container')->html());
    }

    public function testMemberEditWithShop()
    {
        $Member = $this->createMember();
        $Shop = $this->createShop();

        $crawler = $this->client->request('GET', $this->generateUrl('admin_setting_system_member_edit', ['id' => $Member->getId()]));
        $this->assertContains($Shop->getName(), $crawler->filter('body .c-container')->html());
    }

    public function testMemberEditWithoutShopSelect()
    {
        $this->createShop();

        // New member
        $formData = $this->createMemberFormData();
        $formData[self::SHOP] = '';

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_setting_system_member_new'),
            ['admin_member' => $formData]
        );

        $arrTmp = explode('/', $client->getResponse()->getTargetUrl());
        $memberId = $arrTmp[count($arrTmp) - 2];

        $this->assertTrue($client->getResponse()->isRedirection());
        $client->followRedirect();

        // Edit member test
        $formData = $this->createMemberFormData();
        $formData[self::SHOP] = '';

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_setting_system_member_edit', ['id' => $memberId]),
            ['admin_member' => $formData]
        );

        $this->assertTrue($client->getResponse()->isRedirection());
        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check database
        $Member = $this->memberRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Member->getShop();
        $this->expected = null;
        $this->verify();
    }

    public function testMemberEditWithAddShopInvalid()
    {
        $this->createShop();

        // New member
        $formData = $this->createMemberFormData();
        $formData[self::SHOP] = '';

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_setting_system_member_new'),
            ['admin_member' => $formData]
        );

        $arrTmp = explode('/', $client->getResponse()->getTargetUrl());
        $memberId = $arrTmp[count($arrTmp) - 2];

        $client->followRedirect();

        // Edit member test
        $formData = $this->createMemberFormData();
        $formData[self::SHOP] = 99999;

        /**
         * @var Client
         */
        $client = $this->client;
        $crawler = $client->request(
            'POST',
            $this->generateUrl('admin_setting_system_member_edit', ['id' => $memberId]),
            ['admin_member' => $formData]
        );

        // Check message
        $this->assertContains('有効な値ではありません。', $crawler->filter('.form-error-message')->html());

        // Check database
        $Member = $this->memberRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Member->getShop();
        $this->expected = null;
        $this->verify();
    }

    public function testMemberEditWithAddShop()
    {
        $Shop = $this->createShop();

        // New member
        $formData = $this->createMemberFormData();
        $formData[self::SHOP] = '';

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_setting_system_member_new'),
            ['admin_member' => $formData]
        );

        $arrTmp = explode('/', $client->getResponse()->getTargetUrl());
        $memberId = $arrTmp[count($arrTmp) - 2];

        $client->followRedirect();

        // Edit member test
        $formData = $this->createMemberFormData();
        $formData[self::SHOP] = $Shop->getId();

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_setting_system_member_edit', ['id' => $memberId]),
            ['admin_member' => $formData]
        );

        $this->assertTrue($client->getResponse()->isRedirection());
        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check database
        $Member = $this->memberRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = [$Member->getShop()->getId()];
        $this->expected = [$Shop->getId()];
        $this->verify();
    }
}
