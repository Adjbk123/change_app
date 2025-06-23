<?php

namespace App\Form;

use App\Entity\ApproCaisse;
use App\Entity\Caisse;
use App\Entity\CompteAgence;
use App\Entity\CompteCaisse;
use App\Entity\Devise;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApproCaisseForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', null, [
                'attr' => [
                    'class' => 'money-input',
                ]
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApproCaisse::class,
        ]);
    }
}
