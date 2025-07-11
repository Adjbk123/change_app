<?php

namespace App\Form;

use App\Entity\Banque;
use App\Entity\CompteBancaire;
use App\Entity\Devise;
use App\Entity\Pays;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompteBancaireForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numeroBancaire', null, [
                'label' => 'Numéro bancaire',
            ])
            ->add('banque', null, [
                'label' => 'Banque',
                'class' => Banque::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Selectionner une banque --',
            ])
            ->add('pays', EntityType::class, [
                'class' => Pays::class,
                'choice_label' => 'nom',
                'label' => 'Pays',
                'placeholder' => '-- Selectionner une pays --',
            ])
            ->add('devise', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'nom',
                'label' => 'Devise',
                'placeholder' => '-- Choisissez une devise --',
            ])
            ->add('seuilAlerte', null, [
                'label' => 'Seuil d’alerte',
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CompteBancaire::class,
        ]);
    }
}
