<?php

namespace Plugin\ShoppingMall;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShoppingMallEvent implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Setting/System/member_edit.twig' => ['onTemplateMemberEdit'],
        ];
    }

    /**
     * Append JS
     *
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateMemberEdit(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@ShoppingMall/admin/member.twig');
    }
}
