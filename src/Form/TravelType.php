<?php

namespace App\Form;

use App\Entity\Car;
use App\Entity\Travel;
use DateTime;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TravelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'min' => (new \DateTimeImmutable())->format('Y-m-d H:i'),
                    'max' => (new \DateTimeImmutable('+1 month'))->format('Y-m-d H:i'),
                    'autocomplete' => 'off'
                ]
            ])
            ->add('car', EntityType::class, [
                'class' => Car::class,
                'choices' => $options['cars'],
                'choice_label' => function(Car $car) {
                    return sprintf('%s - %s %s', 
                        $car->getPlate(), 
                        $car->getBrand()->getName(),
                        $car->getModel()->getName()
                    );
                },
                'placeholder' => 'Sélectionnez une voiture',
                'required' => true,
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Travel::class,
            'cars' => [],
        ]);
        
        $resolver->setRequired('cars');
        $resolver->setAllowedTypes('cars', 'iterable');
    }
}
