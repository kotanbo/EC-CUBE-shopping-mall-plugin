<?php

namespace Plugin\ShoppingMall\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Eccube\Entity\ClassCategory;
use Eccube\Entity\ClassName;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Request\Context;

class ShoppingMallEventSubscriber implements EventSubscriber
{
    /**
     * @var Context
     */
    private $requestContext;

    /**
     * ShoppingMallEventSubscriber constructor.
     *
     * @param Context $requestContext
     */
    public function __construct(Context $requestContext)
    {
        $this->requestContext = $requestContext;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::postLoad,
        ];
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $this->setShop($entity);
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $this->setShop($entity);
    }

    private function setShop($entity)
    {
        if ($entity instanceof Product) {
            $Member = $this->requestContext->getCurrentUser();
            if (!is_null($Member) && $Member->isShop()) {
                $entity->setShop($Member->getShop());
            }
        }
        if ($entity instanceof ProductClass) {
            $Member = $this->requestContext->getCurrentUser();
            if (!is_null($Member) && $Member->isShop()) {
                $entity->setShop($Member->getShop());
            }
        }
        if ($entity instanceof ClassCategory) {
            $Member = $this->requestContext->getCurrentUser();
            if (!is_null($Member) && $Member->isShop()) {
                $entity->setShop($Member->getShop());
            }
        }
        if ($entity instanceof ClassName) {
            $Member = $this->requestContext->getCurrentUser();
            if (!is_null($Member) && $Member->isShop()) {
                $entity->setShop($Member->getShop());
            }
        }
    }
}
