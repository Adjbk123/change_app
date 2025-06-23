<?php

namespace App\Form;

use App\Entity\Agence;
use App\Entity\Devise;
use App\Entity\TauxChange;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TauxChangeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('deviseSource', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisissez une devise source --',
            ])
            ->add('deviseCible', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisissez une devise cible --',
            ])
            ->add('tauxAchat')
            ->add('tauxVente')

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TauxChange::class,
        ]);
    }
}
