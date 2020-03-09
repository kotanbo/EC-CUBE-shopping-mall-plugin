<?php

namespace Plugin\ShoppingMall;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Member;
use Eccube\Event\TemplateEvent;
use Eccube\Request\Context;
use Plugin\ShoppingMall\Doctrine\Filter\OwnShopProductFilter;
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
     * ProductTypeExtension constructor.
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
            '@admin/Setting/System/member_edit.twig' => ['onTemplateMemberEdit'],
            '@admin/Product/product.twig' => ['onTemplateProductEdit'],
            KernelEvents::CONTROLLER => ['onKernelController'],
        ];
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateMemberEdit(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@ShoppingMall/admin/member.twig');
    }

    /**
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateProductEdit(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@ShoppingMall/admin/product.twig');
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
                $config->addFilter('onw_shop_product', 'Plugin\ShoppingMall\Doctrine\Filter\OwnShopProductFilter');
                /** @var OwnShopProductFilter $filter */
                $filter = $this->entityManager->getFilters()->enable('onw_shop_product');
                $filter->setShopId($Member->getShop());
            }
        }
    }
}
