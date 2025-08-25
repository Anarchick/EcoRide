<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Votre prénom est requis']),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s-\']+$/',
                        'message' => 'Votre prénom ne doit contenir que des lettres, des espaces ou des tirets.'
                    ])
                ]
            ])
            ->add('last_name', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Votre nom est requis']),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s-\']+$/',
                        'message' => 'Votre nom ne doit contenir que des lettres, des espaces ou des tirets.'
                    ])
                ]
            ])
            ->add('username', TextType::class, [
                'constraints' => [
                    new Length([
                        'min' => 3,
                        'max' => 20,
                        'minMessage' => 'Votre pseudonyme doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Votre pseudonyme ne doit pas dépasser {{ limit }} caractères'
                    ]),
                    new NotBlank(['message' => 'Votre pseudonyme est requis']),
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9_-]{3,20}$/',
                        'message' => 'Votre pseudonyme ne doit contenir que des lettres, des chiffres, des tirets ou des underscores.'
                    ])
                ]
            ])
            ->add('phone', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Votre téléphone portable est requis']),
                    new Regex([
                        'pattern' => '/^(?:(?:\+|00)33|0)((?:6|7)(?:[\s\.-]\d{2}){4})\s*$/',
                        'message' => 'Votre numéro de téléphone portable est invalide.'
                    ])
                ]
            ])
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('confirm_password', PasswordType::class, [
                'mapped' => false
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
