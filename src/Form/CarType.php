<?php

namespace App\Form;

use App\Entity\Brand;
use App\Entity\Car;
use App\Entity\Model;
use App\Enum\ColorEnum;
use App\Enum\FuelTypeEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plate', TextType::class, [
                'label' => 'Plaque d\'immatriculation',
                'attr' => [
                    'pattern' => '^[a-zA-Z]{2}-\d{3}-[a-zA-Z]{2}$',
                    'class' => 'form-control',
                    'data-transform' => 'uppercase',
                    'data-validation-message' => 'Format invalide. Attendu : XX-123-XX',
                    'placeholder' => 'AB-123-CD'
                ]
            ])
            ->add('firstRegistrationDate', DateType::class, [
                'label' => 'Date de première immatriculation',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('brand', EntityType::class, [
                'class' => Brand::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionnez une marque',
                'label' => 'Marque',
                'attr' => [
                    'class' => 'form-select',
                    'data-hx-url-template' => '/api/car/models/{value}',
                    'data-hx-target' => '#model-select-wrapper',
                    'data-hx-swap' => 'innerHTML'
                ],
                'required' => true
            ])
            ->add('fuelType', EnumType::class, [
                'class' => FuelTypeEnum::class,
                'label' => 'Type de carburant',
                'attr' => ['class' => 'form-select']
            ])
            ->add('color', EnumType::class, [
                'class' => ColorEnum::class,
                'label' => 'Couleur',
                'attr' => ['class' => 'form-select']
            ])
            ->add('totalSeats', IntegerType::class, [
                'label' => 'Nombre de places totales',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 2,
                    'max' => 10
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;

        // Dynamic model field based on selected brand
        $formModifier = function (FormInterface $form, ?Brand $brand = null): void {
            $models = $brand === null ? [] : $brand->getModels();

            $form->add('model', EntityType::class, [
                'class' => Model::class,
                'choices' => $models,
                'placeholder' => $brand ? 'Sélectionnez un modèle' : 'Sélectionnez d\'abord une marque',
                'label' => 'Modèle',
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'model-select'
                ],
                'required' => true
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier): void {
                $car = $event->getData();
                $formModifier($event->getForm(), $car?->getBrand());
            }
        );

        $builder->get('brand')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier): void {
                $brand = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $brand);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}
