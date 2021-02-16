<?php

namespace Plugin\ShoppingMall\Controller\Admin;

use Plugin\ProductReview4\Form\Type\Admin\ProductReviewConfigType;
use Plugin\ProductReview4\Repository\ProductReviewConfigRepository;
use Plugin\ShoppingMall\Form\Type\Admin\ShoppingMallConfigType;
use Plugin\ShoppingMall\Repository\ShoppingMallConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConfigController.
 */
class ConfigController extends \Eccube\Controller\AbstractController
{
    /**
     * @Route("/%eccube_admin_route%/shopping_mall/config", name="shopping_mall_admin_config")
     * @Template("@ShoppingMall/admin/config.twig")
     *
     * @param Request $request
     * @param ShoppingMallConfigRepository $configRepository
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
     */
    public function index(Request $request, ShoppingMallConfigRepository $configRepository)
    {
        $Config = $configRepository->get();
        $form = $this->createForm(ShoppingMallConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('Shopping mall config', ['status' => 'Success']);
            $this->addSuccess('shopping_mall.admin.config.save.complete', 'admin');

            return $this->redirectToRoute('shopping_mall_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
