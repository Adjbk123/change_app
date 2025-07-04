<?php

namespace App\Form;

use App\Entity\CompteAgence;
use App\Entity\Depense;
use App\Entity\Devise;
use App\Entity\TypeDepense;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepenseForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeDepense', EntityType::class, [
                'class' => TypeDepense::class,
                'choice_label' => 'libelle',
                "placeholder" => "-- Choisir le type de dépense --",
            ])
            ->add('devise', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'nom',
                "placeholder" => "-- Choisir une devise --",
            ])
            ->add('montant', null, [
                'label' => 'Montant',
                'attr' => [
                    'class' => 'money-input',
                ]
            ])
            ->add('motif')

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Depense::class,
        ]);
    }
}
