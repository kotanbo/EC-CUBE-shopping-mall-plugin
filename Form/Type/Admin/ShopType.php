<?php

namespace Plugin\ShoppingMall\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Validator\Email;
use Plugin\ShoppingMall\Entity\Shop;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ShopType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ShopType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'required' => true,
            'attr' => [
                'maxlength' => 255,
            ],
            'constraints' => [
                new NotBlank(),
                new Length(['max' => 255]),
            ],
        ])->add('order_email', TextType::class, [
            'required' => false,
            'attr' => [
                'maxlength' => 255,
            ],
            'constraints' => [
                new Length(['max' => 255]),
                new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
            ],
        ])->add('memo', TextareaType::class, [
            'required' => false,
            'attr' => [
                'maxlength' => 4000,
            ],
            'constraints' => [
                new Length(['max' => 4000]),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Shop::class,
        ]);
    }
}
