<?php

namespace App\Form;

use App\Search\TravelCriteria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TravelSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('departure', TextType::class)
            ->add('arrival', TextType::class)
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d'),
                    'max' => (new \DateTime('+1 month'))->format('Y-m-d'),
                    'value' => (new \DateTime())->format('Y-m-d')
                ]
            ])
            ->add('passengersMin', IntegerType::class, [
                'attr' => [
                    'min' => 1,
                    'max' => 8
                ]
            ])
            // Do not add a submit button for this form
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false, // Disable for GET requests
            'data_class' => TravelCriteria::class,
        ]);
    }
}
