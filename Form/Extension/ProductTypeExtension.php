<?php

namespace Plugin\ShoppingMall\Form\Extension;

use Eccube\Form\Type\Admin\ProductType;
use Eccube\Request\Context;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
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
        $Member = $this->requestContext->getCurrentUser();
        $required = false;
        if (!is_null($Member) && $Member->isShop()) {
            $required = true;
        }
        $constraints = [
            new Assert\Url(),
            new Assert\Length(['max' => 1024]),
        ];
        if ($required) {
            $constraints[] = new Assert\NotBlank();
        }

        $builder
            ->add('external_sales_url', TextType::class, [
                'label' => 'shopping_mall.admin.product.external_sales_url',
                'required' => $required,
                'attr' => [
                    'maxlength' => 1024,
                    'placeholder' => 'shopping_mall.admin.product.external_sales_url.placeholder',
                ],
                'eccube_form_options' => [
                    'auto_render' => false,
                ],
                'constraints' => $constraints,
            ])
            ->add('should_show_price', CheckboxType::class, [
                'required' => false,
                'label' => 'shopping_mall.admin.product.should_show_price',
                'value' => '1',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::class;
    }
}
