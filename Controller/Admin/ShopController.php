<?php

namespace Plugin\ShoppingMall\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\SaleType;
use Eccube\Repository\Master\SaleTypeRepository;
use Plugin\ShoppingMall\Entity\Shop;
use Plugin\ShoppingMall\Form\Type\Admin\ShopType;
use Plugin\ShoppingMall\Repository\ShopRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class ShopController extends AbstractController
{
    /**
     * @var ShopRepository
     */
    protected $shopRepository;

    /**
     * @var SaleTypeRepository
     */
    protected $saleTypeRepository;

    /**
     * ConfigController constructor.
     *
     * @param ShopRepository $shopRepository
     * @param SaleTypeRepository $saleTypeRepository
     */
    public function __construct(ShopRepository $shopRepository, SaleTypeRepository $saleTypeRepository)
    {
        $this->shopRepository = $shopRepository;
        $this->saleTypeRepository = $saleTypeRepository;
    }

    /**
     * List shop.
     *
     * @param Request $request
     *
     * @return array
     *
     * @Route("/%eccube_admin_route%/shopping_mall/shop", name="shopping_mall_admin_shop_index")
     * @Template("@ShoppingMall/admin/Shop/index.twig")
     */
    public function index(Request $request)
    {
        $Shops = $this->shopRepository
            ->findBy(
                [],
                ['sort_no' => 'DESC']
            );

        return [
            'Shops' => $Shops,
        ];
    }

    /**
     * Add/Edit shop.
     *
     * @param Request $request
     * @param Shop|null $Shop
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @Route("/%eccube_admin_route%/shopping_mall/shop/new", name="shopping_mall_admin_shop_new")
     * @Route("/%eccube_admin_route%/shopping_mall/shop/{id}/edit", requirements={"id" = "\d+"}, name="shopping_mall_admin_shop_edit")
     * @Template("@ShoppingMall/admin/Shop/edit.twig")
     */
    public function edit(Request $request, Shop $Shop = null)
    {
        if (is_null($Shop)) {
            $Shop = $this->shopRepository->findOneBy([], ['sort_no' => 'DESC']);
            $sortNo = 1;
            if ($Shop) {
                $sortNo = $Shop->getSortNo() + 1;
            }

            $Shop = new Shop();
            $Shop
                ->setSortNo($sortNo);
        }

        $builder = $this->formFactory
            ->createBuilder(ShopType::class, $Shop);

        $form = $builder->getForm();
        $form->setData($Shop);
        $form->handleRequest($request);

        // 登録ボタン押下
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Shop $Shop */
            $Shop = $form->getData();
            if (is_null($Shop->getSaleType())) {
                // 販売種別登録処理
                $id = $this->saleTypeRepository->createQueryBuilder('st')
                    ->select('MAX(st.id)')
                    ->getQuery()
                    ->getSingleScalarResult();
                if (!$id) {
                    $id = 0;
                }
                $sortNo = $this->saleTypeRepository->createQueryBuilder('st')
                    ->select('MAX(st.sort_no)')
                    ->getQuery()
                    ->getSingleScalarResult();
                if (!$sortNo) {
                    $sortNo = 0;
                }
                $SaleType = new SaleType();
                $SaleType->setId($id + 1);
                $SaleType->setName($Shop->getName());
                $SaleType->setSortNo($sortNo + 1);
                $this->entityManager->persist($SaleType);
                // ショップ登録処理
                $Shop->setSaleType($SaleType);
            }
            // ショップ登録処理
            $this->entityManager->persist($Shop);
            // 登録実行
            $this->entityManager->flush();

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('shopping_mall_admin_shop_edit', ['id' => $Shop->getId()]);
        }

        return [
            'form' => $form->createView(),
            'shop_id' => $Shop->getId(),
            'Shop' => $Shop,
        ];
    }

    /**
     * Delete shop.
     *
     * @param Request $request
     * @param Shop $Shop
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route(
     *     "/%eccube_admin_route%/shopping_mall/shop/{id}/delete",
     *     name="shopping_mall_admin_shop_delete", requirements={"id":"\d+"},
     *     methods={"DELETE"}
     * )
     */
    public function delete(Request $request, Shop $Shop)
    {
        $this->isTokenValid();

        try {
            $this->shopRepository->delete($Shop);

            $this->addSuccess('shopping_mall.admin.shop.delete.complete', 'admin');

            log_info('店舗削除完了', ['Shop id' => $Shop->getId()]);
        } catch (\Exception $e) {
            log_info('店舗削除エラー', ['Shop id' => $Shop->getId(), $e]);

            $message = trans('admin.delete.failed.foreign_key', ['%name%' => $Shop->getName()]);
            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('shopping_mall_admin_shop_index');
    }

    /**
     * Move sort no with ajax.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     *
     * @Route(
     *     "/%eccube_admin_route%/shopping_mall/shop/move_sort_no",
     *     name="shopping_mall_admin_shop_move_sort_no",
     *     methods={"POST"}
     * )
     */
    public function moveSortNo(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $shopId => $sortNo) {
                $Shop = $this->shopRepository->find($shopId);
                $Shop->setSortNo($sortNo);
                $this->entityManager->persist($Shop);
            }
            $this->entityManager->flush();
        }

        return new Response();
    }
}
