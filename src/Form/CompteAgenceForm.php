<?php

namespace App\Form;

use App\Entity\Agence;
use App\Entity\CompteAgence;
use App\Entity\Devise;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompteAgenceForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('devise', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'nom',
            ])
            ->add('sueilAlerte', null, [
                'attr' => [
                    'class' => 'money-input',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CompteAgence::class,
        ]);
    }
}
