<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\CompteClient;
use App\Entity\Devise;
use App\Entity\Operation;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OperationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeOperation')
            ->add('montantSource')
            ->add('montantCible')
            ->add('taux')
            ->add('sens')
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->add('updatedAt', null, [
                'widget' => 'single_text',
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'id',
            ])
            ->add('compteClientSource', EntityType::class, [
                'class' => CompteClient::class,
                'choice_label' => 'id',
            ])
            ->add('compteClientCible', EntityType::class, [
                'class' => CompteClient::class,
                'choice_label' => 'id',
            ])
            ->add('deviseSource', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'id',
            ])
            ->add('deviseCible', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'id',
            ])
            ->add('agent', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Operation::class,
        ]);
    }
}
