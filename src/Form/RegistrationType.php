<?php

namespace App\Form;

use App\Entity\User;
use App\Form\DataTransformer\PhoneNumberTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'attr' => ['autocomplete' => 'given-name'],
            ])
            ->add('lastName', TextType::class, [
                'attr' => ['autocomplete' => 'family-name'],
            ])
            ->add('email', EmailType::class, [
                'attr' => ['autocomplete' => 'email username'],
                'constraints' => [
                    new Email(message: 'Saisissez une adresse email valide'),
                    new Length(
                        max: 180,
                        maxMessage: 'Votre adresse email ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])
            ->add('username', TextType::class, [
                'attr' => ['autocomplete' => 'nickname'],
            ])
            ->add('phone', TelType::class, [ // TelType can adapt keyboard on mobile
                'attr' => ['autocomplete' => 'tel'],
                // French phone number format validation
                'constraints' => new Regex(
                    pattern:'/^(?:(?:\+|00)?33[\s\.-]|0)?((?:6|7)(?:[\s\.-]?\d{2}){4})\s*$/',
                    message: 'Votre numéro de téléphone portable est invalide.'
                )
            ])
            // Not in User, constraints are added here
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'first_options' => [
                    // Keeps the value during errors for better UX
                    // This is a lower security issue accepted due to HTTPS in production
                    'always_empty' => false,
                    'attr' => ['autocomplete' => 'new-password'], // Navigator can suggest password
                ],
                'second_options' => [
                    'always_empty' => false,
                    'attr' => ['autocomplete' => 'new-password'], // Navigator can suggest password
                ],
                'constraints' => [
                    new NotBlank(message: 'Saisissez un mot de passe'),
                    new Length(
                        min: 6,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        max: 128
                    ),
                    new PasswordStrength(minScore: 2, message: 'Le mot de passe est trop faible. Utilisez des majuscules, minuscules, chiffres et symboles.'),
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(message: 'Vous devez accepter nos conditions.'),
                ],
            ])
            ->add('submit', SubmitType::class)
        ;

        $builder->get('phone')->addModelTransformer(new PhoneNumberTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
