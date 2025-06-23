<?php

namespace App\Form;

use App\Entity\ApproCompteGeneral;
use App\Entity\CompteGeneral;
use App\Entity\Devise;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApproCompteGeneralForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', null, [
                'label' => 'Montant à créditer',
                'attr' => [
                    'class' => 'money-input',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApproCompteGeneral::class,
        ]);
    }
}
