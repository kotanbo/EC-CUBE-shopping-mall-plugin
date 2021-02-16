<?php

namespace Plugin\ShoppingMall\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Plugin\ShoppingMall\Entity\ShoppingMallConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ProductReviewConfigType.
 */
class ShoppingMallConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ShoppingMallConfigType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('needs_external_sales_url', ChoiceType::class, [
                'label' => 'shopping_mall.admin.config.needs_external_sales_url',
                'choices' => [
                    'shopping_mall.admin.config.needs_external_sales_url.yes' => true,
                    'shopping_mall.admin.config.needs_external_sales_url.no' => false,
                ],
                'expanded' => true,
                'required' => true,
            ]);
    }

    /**
     * Config.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShoppingMallConfig::class,
        ]);
    }
}
