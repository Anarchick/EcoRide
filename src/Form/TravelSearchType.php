<?php

namespace App\Form;

use App\Enum\LuggageSizeEnum;
use App\Form\DataTransformer\LuggageSizeTransformer;
use App\Model\Search\TravelCriteria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TravelSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Main search criteria
            ->add('departure', TextType::class, [
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('arrival', TextType::class, [
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d'),
                    'max' => (new \DateTime('+1 month'))->format('Y-m-d'),
                    'autocomplete' => 'off'
                ]
            ])
            ->add('minPassengers', IntegerType::class, [
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            // Filters criteria

            ->add('isElectricPreferred', CheckboxType::class, [
                'required' => false,
            ])
            ->add('isSmokingAllowed', CheckboxType::class, [
                'required' => false,
            ])
            ->add('isPetsAllowed', CheckboxType::class, [
                'required' => false,
            ])
            ->add('luggageSizeMin', ChoiceType::class, [
                'required' => false,
                'choices' => LuggageSizeEnum::getChoices(),
            ])
            ->add('maxCost', RangeType::class, [
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'value' => 100,
                ]
            ])
            ->add('maxDuration', RangeType::class, [
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => 24,
                    'value' => 24,
                ]
            ])
            ->add('minScore', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Toutes les notes' => 0,
                    '1+' => 1,
                    '2+' => 2,
                    '3+' => 3,
                    '4+' => 4,
                    '5' => 5,
                ],
            ])

            // Do not add a submit button for this form
        ;

        $builder->get('luggageSizeMin')->addModelTransformer(new LuggageSizeTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false, // Disable for GET requests
            'data_class' => TravelCriteria::class,
        ]);
    }
}
