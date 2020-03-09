<?php

namespace Plugin\ShoppingMall;

use Doctrine\ORM\EntityManager;
use Eccube\Entity\AuthorityRole;
use Eccube\Entity\Master\Authority;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\AuthorityRoleRepository;
use Eccube\Repository\Master\AuthorityRepository;
use Eccube\Repository\MemberRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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
     */
    const DENY_URLS = [
        '/product/category',
        '/product/tag',
        '/product/category_csv_upload',
        '/order',
        '/customer',
        '/content',
        '/setting',
        '/store',
        '/shopping_mall',
    ];

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
        $authority = new Authority();
        $authority->setId($id + 1);
        $authority->setName(self::AUTHORITY_NAME);
        $authority->setSortNo($sortNo + 1);
        $authorityRepository->save($authority);

        // 作成した権限を保持
        file_put_contents(
            self::SHOP_AUTHORITY_XML_PATH,
            $authority->toXML()
        );

        foreach (self::DENY_URLS as $denyUrl) {
            $authorityRole = new AuthorityRole();
            $authorityRole->setAuthority($authority);
            $authorityRole->setDenyUrl($denyUrl);
            $authorityRoleRepository->save($authorityRole);
        }
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
        /** @var MemberRepository $memberRepository */
        $memberRepository = $container->get(MemberRepository::class);
        /** @var AuthorityRepository $authorityRepository */
        $authorityRepository = $container->get(AuthorityRepository::class);

        $authority = self::getShopAuthority();
        if (!is_null($authority)) {
            $authority = $authorityRepository->find($authority->getId());
        }
        if (!is_null($authority)) {
            $count = $memberRepository->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->where('m.Authority = :authority')
                ->setParameter('authority', $authority)
                ->getQuery()
                ->getSingleScalarResult();
            if ($count <= 0) {
                /** @var EntityManager $em */
                $em = $container->get('doctrine.orm.entity_manager');
                $qb = $em->createQueryBuilder()
                    ->delete(AuthorityRole::class, 'a')
                    ->where('a.Authority = :authority')
                    ->setParameter('authority', $authority)
                    ->getQuery()
                    ->execute();
                $em->remove($authority);
                $em->flush($authority);
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
        $authority = null;
        if (file_exists(self::SHOP_AUTHORITY_XML_PATH)) {
            $xml = file_get_contents(self::SHOP_AUTHORITY_XML_PATH);
            if ($xml !== false) {
                $encoders = [new XmlEncoder(), new JsonEncoder()];
                $normalizers = [new ObjectNormalizer()];
                $serializer = new Serializer($normalizers, $encoders);
                /** @var Authority $authority */
                $authority = $serializer->deserialize($xml, Authority::class, 'xml');
            }
        }

        return $authority;
    }
}
