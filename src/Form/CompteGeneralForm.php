<?php

namespace App\Form;

use App\Entity\CompteGeneral;
use App\Entity\Devise;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompteGeneralForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder


            ->add('devise', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisissez une devise --',
            ])
            ->add('seuilAlerte', null, [
                'attr' => [
                    'class' => 'money-input',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CompteGeneral::class,
        ]);
    }
}
