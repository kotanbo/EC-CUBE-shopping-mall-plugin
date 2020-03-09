<?php

namespace Plugin\ShoppingMall\Form\Extension;

use Eccube\Entity\Product;
use Eccube\Form\Type\Admin\ProductType;
use Eccube\Request\Context;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

class ProductTypeExtension extends AbstractTypeExtension
{
    /**
     * @var Context
     */
    protected $requestContext;

    /**
     * ProductTypeExtension constructor.
     *
     * @param Context $requestContext
     */
    public function __construct(Context $requestContext)
    {
        $this->requestContext = $requestContext;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('external_sales_url', TextType::class, [
                'label' => 'shopping_mall.admin.product.external_sales_url',
                'attr' => [
                    'maxlength' => 1024,
                    'placeholder' => 'shopping_mall.admin.product.external_sales_url.placeholder',
                ],
                'eccube_form_options' => [
                    'auto_render' => true,
                ],
                'constraints' => [
                    new Assert\Url(),
                    new Assert\Length(['max' => 1024]),
                ],
            ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var Product $Product */
            $Product = $event->getData();
            if (!is_null($Product)) {
                $Member = $this->requestContext->getCurrentUser();
                if (!is_null($Member)) {
                    $Product->setShop($Member->getShop());
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::class;
    }
}
