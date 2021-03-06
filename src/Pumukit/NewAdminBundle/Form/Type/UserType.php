<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('enabled', HiddenType::class, ['data' => true])
            ->add(
                'fullname',
                TextType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Name and Surname', [], null, $this->locale)],
                    'label' => $this->translator->trans('Name and Surname', [], null, $this->locale),
                ]
            )
            ->add(
                'username',
                TextType::class,
                [
                    'attr' => [
                        'aria-label' => $this->translator->trans('Username', [], null, $this->locale),
                        'autocomplete' => 'off',
                        'pattern' => '^[a-zA-Z0-9_\-\.@]{4,32}$',
                        'oninvalid' => "setCustomValidity('The username can not have blank spaces neither special characters')",
                        'oninput' => "setCustomValidity('')",
                    ],
                    'label' => $this->translator->trans('Username', [], null, $this->locale),
                ]
            )
            ->add(
                'plain_password',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'invalid_message' => $this->translator->trans('The password fields must match.'),
                    'first_options' => ['label' => $this->translator->trans('Password')],
                    'second_options' => ['label' => $this->translator->trans('Repeat Password')],
                    'attr' => ['autocomplete' => 'off', 'aria-label' => $this->translator->trans('Password', [], null, $this->locale)],
                    'required' => false,
                    'label' => $this->translator->trans('Password', [], null, $this->locale),
                ]
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Email', [], null, $this->locale)],
                    'label' => $this->translator->trans('Email', [], null, $this->locale),
                ]
            )
            ->add(
                'permissionProfile',
                null,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Permission Profile', [], null, $this->locale)],
                    'label' => $this->translator->trans('Permission Profile', [], null, $this->locale),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix(): string
    {
        return 'pumukitnewadmin_user';
    }
}
