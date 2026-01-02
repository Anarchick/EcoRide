<?php

namespace App\Form;

use App\Entity\TravelPreference;
use App\Enum\LuggageSizeEnum;
use App\Form\DataTransformer\LuggageSizeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TravelPreferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isSmokingAllowed', CheckboxType::class, [
                'required' => false,
            ])
            ->add('isPetsAllowed', CheckboxType::class, [
                'required' => false,
            ])
            ->add('luggageSize', ChoiceType::class, [
                'choices' => LuggageSizeEnum::getChoices(),
                'data' => LuggageSizeEnum::NONE,
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
            ])
        ;

        $builder->get('luggageSize')->addModelTransformer(new LuggageSizeTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TravelPreference::class,
        ]);
    }

}
