<?php

namespace Plugin\ShoppingMall;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Event\TemplateEvent;
use Eccube\Request\Context;
use Plugin\ShoppingMall\Doctrine\Filter\OwnShopFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
     * ShoppingMallEvent constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param Context $requestContext
     */
    public function __construct(EntityManagerInterface $entityManager, Context $requestContext)
    {
        $this->entityManager = $entityManager;
        $this->requestContext = $requestContext;
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
            '@admin/Setting/System/member_edit.twig' => ['onTemplateMemberEdit'],
            '@admin/Product/product.twig' => ['onTemplateProductEdit'],
            '@admin/index.twig' => ['onTemplateHome'],
            KernelEvents::CONTROLLER => ['onKernelController'],
            // フロント画面側
            'Product/list.twig' => ['onTemplateProductList'],
            'Product/detail.twig' => ['onTemplateProductDetail'],
        ];
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateMemberEdit(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@ShoppingMall/admin/Setting/System/member_edit.twig');
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateProductEdit(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@ShoppingMall/admin/Product/product.twig');
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateHome(TemplateEvent $templateEvent)
    {
        $Member = $this->requestContext->getCurrentUser();
        if (!is_null($Member) && $Member->isShop()) {
            $templateEvent->addSnippet('@ShoppingMall/admin/index.twig');
        }
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if ($this->requestContext->isAdmin()) {
            $Member = $this->requestContext->getCurrentUser();
            if (!is_null($Member) && $Member->isShop()) {
                $config = $this->entityManager->getConfiguration();
                $config->addFilter('onw_shop_product', OwnShopFilter::class);
                /** @var OwnShopFilter $filter */
                $filter = $this->entityManager->getFilters()->enable('onw_shop_product');
                $filter->setShopId($Member->getShop());
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
}
