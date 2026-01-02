<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TravelBookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slots', IntegerType::class, [
                'mapped' => false,
                'attr' => [
                    'value' => $options['default_slot'],
                    'min' => 1,
                    'max' => $options['max_slot'],
                    'autocomplete' => 'off'
                ]
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'default_slot' => 1,
            'max_slot' => 1,
        ]);
        
        $resolver->setRequired(['default_slot', 'max_slot']);
        $resolver->setAllowedTypes('default_slot', 'int');
        $resolver->setAllowedTypes('max_slot', 'int');
    }
}
