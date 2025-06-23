<?php

namespace App\Form;

use App\Entity\CompteClient;
use App\Entity\Devise;
use App\Entity\ProfilClient;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompteClientForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('soldeInitial')
            ->add('soldeActuel')
            ->add('devise', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'id',
            ])
            ->add('profilClient', EntityType::class, [
                'class' => ProfilClient::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CompteClient::class,
        ]);
    }
}
