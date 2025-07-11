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
            ->add('montantPrincipal')
            ->add('montantRestant')
            ->add('tauxInteretAnnuel')
            ->add('dureeMois')
            ->add('montantTotalRembourse')
            ->add('statut')
            ->add('commentaire')
            ->add('profilClient', EntityType::class, [
                'class' => ProfilClient::class,
                'choice_label' => 'id',
            ])
            ->add('devise', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'id',
            ])
            ->add('agentOctroi', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            ->add('agence', EntityType::class, [
                'class' => Agence::class,
                'choice_label' => 'id',
            ])
            ->add('caisse', EntityType::class, [
                'class' => Caisse::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pret::class,
        ]);
    }
}
