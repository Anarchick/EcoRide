<?php

namespace App\Form;

use App\Entity\Travel;
use App\Enum\FuelTypeEnum;
use App\Service\PricingService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Travel2Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $princingService = new PricingService($options['distance_km'], $options['fuel_type']);

        $builder
            ->add('passengersMax', IntegerType::class, [
                'attr' => [
                    'autocomplete' => 'off',
                    'min' => 1,
                    'max' => $options['passengers_max'],
                ]
            ])
            ->add('cost', RangeType::class, [
                'attr' => [
                    'min' => $princingService->getMin(),
                    'max' => $princingService->getMax(),
                    'step' => $princingService->getStep(),
                    'data' => $princingService->getEstimatedPrice(),
                ]
            ])
            ->add('travelPreference', TravelPreferenceType::class, [
                'label' => false,
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Travel::class,
            'passengers_max' => 1,
            'distance_km' => 1,
            'fuel_type' => FuelTypeEnum::ELECTRIC,
        ]);

        $resolver->setAllowedTypes('passengers_max', 'int');
        $resolver->setAllowedTypes('distance_km', 'int');
        $resolver->setAllowedTypes('fuel_type', FuelTypeEnum::class);

        $resolver->setRequired(['passengers_max', 'distance_km', 'fuel_type']);
    }

}
