<?php

namespace Plugin\ShoppingMall\Controller\Admin;

use Eccube\Controller\AbstractController;
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
     * ConfigController constructor.
     *
     * @param ShopRepository $shopRepository
     */
    public function __construct(ShopRepository $shopRepository)
    {
        $this->shopRepository = $shopRepository;
    }

    /**
     * List, add, edit shop.
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @Route("/%eccube_admin_route%/shopping_mall/shop", name="shopping_mall_admin_shop_index")
     * @Template("@ShoppingMall/admin/Shop/index.twig")
     */
    public function index(Request $request)
    {
        $Shop = new Shop();
        $Shops = $this->shopRepository->findBy([], ['sort_no' => 'DESC']);

        /**
         * 新規登録フォーム
         */
        $builder = $this->formFactory->createBuilder(ShopType::class, $Shop);

        $form = $builder->getForm();

        /**
         * 編集用フォーム
         */
        $forms = [];
        foreach ($Shops as $item) {
            $id = $item->getId();
            $forms[$id] = $this->formFactory->createNamed('shop_'.$id, ShopType::class, $item);
        }

        if ('POST' === $request->getMethod()) {
            /*
             * 登録処理
             */
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->shopRepository->save($form->getData());

                $this->addSuccess('shopping_mall.admin.shop.save.complete', 'admin');

                return $this->redirectToRoute('shopping_mall_admin_shop_index');
            }

            /*
             * 編集処理
             */
            foreach ($forms as $editForm) {
                $editForm->handleRequest($request);
                if ($editForm->isSubmitted() && $editForm->isValid()) {
                    $this->shopRepository->save($editForm->getData());

                    $this->addSuccess('shopping_mall.admin.shop.save.complete', 'admin');

                    return $this->redirectToRoute('shopping_mall_admin_shop_index');
                }
            }
        }

        $formViews = [];
        foreach ($forms as $key => $value) {
            $formViews[$key] = $value->createView();
        }

        return [
            'form' => $form->createView(),
            'Shops' => $Shops,
            'Shop' => $Shop,
            'forms' => $formViews,
        ];
    }

    /**
     * Delete Shop.
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
