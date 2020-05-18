<?php

namespace Plugin\ShoppingMall;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Member;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Request\Context;
use Plugin\ShoppingMall\Doctrine\Filter\OwnShopFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class ShoppingMallEvent implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Context
     */
    private $requestContext;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * ShoppingMallEvent constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param Context $requestContext
     * @param RouterInterface $router
     */
    public function __construct(EntityManagerInterface $entityManager, Context $requestContext, RouterInterface $router)
    {
        $this->entityManager = $entityManager;
        $this->requestContext = $requestContext;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // 管理画面側
            '@admin/index.twig' => ['onTemplateAdminHome'],
            '@admin/Product/index.twig' => ['onTemplateAdminProductIndex'],
            '@admin/Product/product.twig' => ['onTemplateAdminProductEdit'],
            '@admin/Order/index.twig' => ['onTemplateAdminOrderIndex'],
            '@admin/Order/edit.twig' => ['onTemplateAdminOrderEdit'],
            '@admin/Setting/System/member_edit.twig' => ['onTemplateAdminMemberEdit'],
            KernelEvents::CONTROLLER => ['onKernelController'],
            KernelEvents::RESPONSE => ['onKernelResponse'],
            // フロント画面側
            'Product/list.twig' => ['onTemplateProductList'],
            'Product/detail.twig' => ['onTemplateProductDetail'],
            EccubeEvents::MAIL_ORDER => ['onMailOrder'],
        ];
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateAdminHome(TemplateEvent $templateEvent)
    {
        if ($this->isShop()) {
            $templateEvent->addSnippet('@ShoppingMall/admin/index.twig');
        }
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateAdminProductIndex(TemplateEvent $templateEvent)
    {
        if ($this->isShop()) {
            $templateEvent->addSnippet('@ShoppingMall/admin/Product/index.twig');
        }
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateAdminProductEdit(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@ShoppingMall/admin/Product/product.twig');
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateAdminOrderIndex(TemplateEvent $templateEvent)
    {
        if ($this->isShop()) {
            $templateEvent->addSnippet('@ShoppingMall/admin/Order/index.twig');
        }
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateAdminOrderEdit(TemplateEvent $templateEvent)
    {
        if ($this->isShop()) {
            $templateEvent->addSnippet('@ShoppingMall/admin/Order/edit.twig');
        }
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateAdminMemberEdit(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@ShoppingMall/admin/Setting/System/member_edit.twig');
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if ($this->requestContext->isAdmin()) {
            $Member = $this->requestContext->getCurrentUser();
            if ($Member instanceof Member && $Member->isShop()) {
                // 自ショップの情報のみ取得するフィルター設定
                $config = $this->entityManager->getConfiguration();
                $config->addFilter('own_shop_product', OwnShopFilter::class);
                /** @var OwnShopFilter $filter */
                $filter = $this->entityManager->getFilters()->enable('own_shop_product');
                $filter->setShopId($Member->getShop());
            }
        }
    }

    /**
     * @param FilterResponseEvent $events
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->requestContext->isAdmin()) {
            $Member = $this->requestContext->getCurrentUser();
            if ($Member instanceof Member && $Member->isShop()) {
                // ショップメンバーは店舗設定の基本設定はアクセス不可、配送方法設定へリダイレクト
                if ($event->getRequest()->getRequestUri() === $this->router->generate('admin_setting_shop')) {
                    $event->setResponse(new RedirectResponse($this->router->generate('admin_setting_shop_delivery')));
                }
            }
        }
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateProductList(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@ShoppingMall/default/Product/list.twig');
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateProductDetail(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@ShoppingMall/default/Product/detail.twig');
    }

    /**
     * @param EventArgs $event
     */
    public function onMailOrder(EventArgs $event)
    {
        /** @var Order $Order */
        $Order = $event->getArgument('Order');
        $Shop = $Order->getShopFromItems();
        if (!is_null($Shop) && !is_null($Shop->getOrderEmail())) {
            /** @var \Swift_Message $message */
            $message = $event->getArgument('message');
            $message->addBcc($Shop->getOrderEmail());
        }
    }

    /**
     * @return bool
     */
    private function isShop()
    {
        $Member = $this->requestContext->getCurrentUser();

        return $Member instanceof Member && $Member->isShop();
    }
}
