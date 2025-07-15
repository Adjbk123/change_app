<?php

namespace App\Form;

use App\Entity\Agence;
use App\Entity\Caisse;
use App\Entity\Devise;
use App\Entity\Pret;
use App\Entity\ProfilClient;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PretForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('profilClient', EntityType::class, [
                'class' => ProfilClient::class,
            ])
            ->add('montantPrincipal', null, [
                'label' => 'Montant du prêt ',
            ])
            ->add('devise', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'nom',
            ])

            ->add('dureeMois', null, [
                'label'=>'Durée en mois du pret',
            ])
            ->add('tauxInteretAnnuel', null, [
                'label' => 'Taux annuel',
            ])

            ->add('commentaire')

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pret::class,
        ]);
    }
}
