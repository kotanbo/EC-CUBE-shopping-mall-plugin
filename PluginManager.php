<?php

namespace Plugin\ShoppingMall;

use Doctrine\ORM\EntityManager;
use Eccube\Entity\AuthorityRole;
use Eccube\Entity\Csv;
use Eccube\Entity\Master\Authority;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Product;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\AuthorityRoleRepository;
use Eccube\Repository\CsvRepository;
use Eccube\Repository\Master\AuthorityRepository;
use Eccube\Repository\Master\CsvTypeRepository;
use Eccube\Repository\MemberRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Eccube\Util\CacheUtil;

/**
 * PluginManager
 */
class PluginManager extends AbstractPluginManager
{
    /**
     * ショップ用権限の名称
     */
    const AUTHORITY_NAME = 'ショップ（ショッピングモールプラグイン用）';

    /**
     * 作成したショップ用権限のエンティティ保存パス
     */
    const SHOP_AUTHORITY_XML_PATH = __DIR__.DIRECTORY_SEPARATOR.'shop_authority.xml';

    /**
     * ショップ用権限の拒否URL一覧
     * /setting/shop（基本設定）は ShoppingMallEvent.php onKernelResponse で拒否
     */
    const DENY_URLS = [
        '/product/category',
        '/product/tag',
        '/product/category_csv_upload',
        '/customer',
        '/content',
        '/setting/shop/payment',
        '/setting/shop/tax',
        '/setting/shop/mail',
        '/setting/shop/csv',
        '/setting/system',
        '/store',
        '/shopping_mall',
    ];

    /**
     * @return Translator
     */
    private function getPluginTranslator()
    {
        $locale = env('ECCUBE_LOCALE');
        $getResourcePath = function ($locale) {
            return __DIR__.DIRECTORY_SEPARATOR.'Resource'.DIRECTORY_SEPARATOR.'locale'.DIRECTORY_SEPARATOR.'messages.'.$locale.'.yaml';
        };
        if (!file_exists($getResourcePath($locale))) {
            $locale = 'ja';
        }
        $translator = new Translator($locale);
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource(
            'yaml',
            $getResourcePath($locale),
            $locale
        );

        return $translator;
    }

    /**
     * Install the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function install(array $meta, ContainerInterface $container)
    {
        /** @var AuthorityRepository $authorityRepository */
        $authorityRepository = $container->get(AuthorityRepository::class);
        /** @var AuthorityRoleRepository $authorityRoleRepository */
        $authorityRoleRepository = $container->get(AuthorityRoleRepository::class);

        $id = $authorityRepository->createQueryBuilder('a')
            ->select('MAX(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        if (!$id) {
            $id = 0;
        }
        $sortNo = $authorityRepository->createQueryBuilder('a')
            ->select('MAX(a.sort_no)')
            ->getQuery()
            ->getSingleScalarResult();
        if (!$sortNo) {
            $sortNo = 0;
        }
        $Authority = new Authority();
        $Authority->setId($id + 1);
        $Authority->setName(self::AUTHORITY_NAME);
        $Authority->setSortNo($sortNo + 1);
        $authorityRepository->save($Authority);

        // 作成した権限を保持
        file_put_contents(
            self::SHOP_AUTHORITY_XML_PATH,
            $Authority->toXML()
        );

        foreach (self::DENY_URLS as $denyUrl) {
            $AuthorityRole = new AuthorityRole();
            $AuthorityRole->setAuthority($Authority);
            $AuthorityRole->setDenyUrl($denyUrl);
            $authorityRoleRepository->save($AuthorityRole);
        }
    }

    /**
     * Update the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     * @throws \Exception
     */
    public function update(array $meta, ContainerInterface $container)
    {
        /** @var AuthorityRepository $authorityRepository */
        $authorityRepository = $container->get(AuthorityRepository::class);
        /** @var AuthorityRoleRepository $authorityRoleRepository */
        $authorityRoleRepository = $container->get(AuthorityRoleRepository::class);

        $Authority = self::getShopAuthority();
        if (!is_null($Authority)) {
            $Authority = $authorityRepository->find($Authority->getId());
        }
        if (!is_null($Authority)) {
            $this->clearAndCreateDenyUrls($Authority, $authorityRoleRepository);
        }

        $translator = $this->getPluginTranslator();
        $message = $translator->trans('shopping_mall.update.warning', [
            '%contents_management%' => trans('admin.content.contents_management'),
            '%cache_management%' => trans('admin.content.cache_management'),
        ]);
        /** @var Session $session */
        $session = $container->get('session');
        $session->getFlashBag()->add('eccube.admin.warning', $message);
    }

    /**
     * Enable the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        /** @var CsvRepository $csvRepository */
        $csvRepository = $container->get(CsvRepository::class);
        /** @var CsvTypeRepository $csvTypeRepository */
        $csvTypeRepository = $container->get(CsvTypeRepository::class);
        /** @var CacheUtil $cacheUtil */
        $cacheUtil = $container->get(CacheUtil::class);
        $translator = $this->getPluginTranslator();

        /** @var CsvType $CsvType */
        $CsvType = $csvTypeRepository->find(CsvType::CSV_TYPE_PRODUCT);
        $sortNo = $csvRepository->createQueryBuilder('c')
            ->select('MAX(c.sort_no)')
            ->where('c.CsvType = :csv_type')
            ->setParameter('csv_type', $CsvType)
            ->getQuery()
            ->getSingleScalarResult();
        if (!$sortNo) {
            $sortNo = 0;
        }
        $Csv = new Csv();
        $Csv->setCsvType($CsvType);
        $Csv->setEntityName(Product::class);
        $Csv->setFieldName('external_sales_url');
        $Csv->setDispName($translator->trans('shopping_mall.admin.product.external_sales_url'));
        $Csv->setSortNo($sortNo + 1);
        $Csv->setEnabled(true);
        $csvRepository->save($Csv);

        $Csv = new Csv();
        $Csv->setCsvType($CsvType);
        $Csv->setEntityName(Product::class);
        $Csv->setFieldName('should_show_price');
        $Csv->setDispName($translator->trans('shopping_mall.admin.product.should_show_price'));
        $Csv->setSortNo($sortNo + 2);
        $Csv->setEnabled(true);
        $csvRepository->save($Csv);

        // キャッシュの削除
        $cacheUtil->clearTwigCache();
    }

    /**
     * Disable the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        /** @var CsvRepository $csvRepository */
        $csvRepository = $container->get(CsvRepository::class);
        /** @var CsvTypeRepository $csvTypeRepository */
        $csvTypeRepository = $container->get(CsvTypeRepository::class);

        /** @var CsvType $CsvType */
        $CsvType = $csvTypeRepository->find(CsvType::CSV_TYPE_PRODUCT);
        /** @var Csv $Csv */
        $Csv = $csvRepository->findOneBy(['CsvType' => $CsvType, 'field_name' => 'external_sales_url']);
        $csvRepository->delete($Csv);
        $Csv = $csvRepository->findOneBy(['CsvType' => $CsvType, 'field_name' => 'should_show_price']);
        $csvRepository->delete($Csv);
    }

    /**
     * Uninstall the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        /** @var AuthorityRepository $authorityRepository */
        $authorityRepository = $container->get(AuthorityRepository::class);
        /** @var MemberRepository $memberRepository */
        $memberRepository = $container->get(MemberRepository::class);

        $Authority = self::getShopAuthority();
        if (!is_null($Authority)) {
            $Authority = $authorityRepository->find($Authority->getId());
        }
        if (!is_null($Authority)) {
            $count = $memberRepository->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->where('m.Authority = :authority')
                ->setParameter('authority', $Authority)
                ->getQuery()
                ->getSingleScalarResult();
            if ($count > 0) {
                $translator = $this->getPluginTranslator();
                $process = $translator->trans('shopping_mall.uninstall.not_deleted_data.info.process', [
                    '%system%' => trans('admin.setting.system'),
                    '%member_management%' => trans('admin.setting.system.member_management'),
                    '%authority%' => trans('admin.common.authority'),
                    '%authority_management%' => trans('admin.setting.system.authority_management'),
                    '%deny_url%' => trans('admin.setting.system.authority.deny_url'),
                    '%master_data_management%' => trans('admin.setting.system.master_data_management'),
                    '%shop_authority%' => $Authority->getName(),
                ]);
                $message = $translator->trans('shopping_mall.uninstall.not_deleted_data.info', [
                    '%process%' => $process,
                ]);
                /** @var Session $session */
                $session = $container->get('session');
                $session->getFlashBag()->add('eccube.admin.info', $message);
            } else {
                /** @var EntityManager $em */
                $em = $container->get('doctrine.orm.entity_manager');
                $em->createQueryBuilder()
                    ->delete(AuthorityRole::class, 'a')
                    ->where('a.Authority = :authority')
                    ->setParameter('authority', $Authority)
                    ->getQuery()
                    ->execute();
                $em->remove($Authority);
                $em->flush($Authority);
            }
        }
        if (file_exists(self::SHOP_AUTHORITY_XML_PATH)) {
            unlink(self::SHOP_AUTHORITY_XML_PATH);
        }
    }

    /**
     * @return Authority|null
     */
    public static function getShopAuthority()
    {
        $Authority = null;
        if (file_exists(self::SHOP_AUTHORITY_XML_PATH)) {
            $xml = file_get_contents(self::SHOP_AUTHORITY_XML_PATH);
            if ($xml !== false) {
                $encoders = [new XmlEncoder(), new JsonEncoder()];
                $normalizers = [new ObjectNormalizer()];
                $serializer = new Serializer($normalizers, $encoders);
                /** @var Authority $Authority */
                $Authority = $serializer->deserialize($xml, Authority::class, 'xml');
            }
        }

        return $Authority;
    }

    /**
     * @param Authority $Authority
     * @param AuthorityRoleRepository $authorityRoleRepository
     */
    private function clearAndCreateDenyUrls(Authority $Authority, AuthorityRoleRepository $authorityRoleRepository)
    {
        $AuthorityRoles = $authorityRoleRepository->findBy(['Authority' => $Authority]);
        foreach ($AuthorityRoles as $AuthorityRole) {
            $authorityRoleRepository->delete($AuthorityRole);
        }
        foreach (self::DENY_URLS as $denyUrl) {
            $AuthorityRole = new AuthorityRole();
            $AuthorityRole->setAuthority($Authority);
            $AuthorityRole->setDenyUrl($denyUrl);
            $authorityRoleRepository->save($AuthorityRole);
        }
    }
}
