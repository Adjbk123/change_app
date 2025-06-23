<?php

namespace App\Form;

use App\Entity\ApproCompteBancaire;
use App\Entity\CompteBancaire;
use App\Entity\Devise;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApproCompteBancaireForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant')
            ->add('dateAppro')
            ->add('compteBancaire', EntityType::class, [
                'class' => CompteBancaire::class,
                'choice_label' => 'id',
            ])
            ->add('devise', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApproCompteBancaire::class,
        ]);
    }
}
