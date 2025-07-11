<?php

namespace App\Form;

use App\Entity\Pret;
use App\Entity\Remboursement;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RemboursementForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montantRembourse')
            ->add('dateRemboursement')
            ->add('typePaiement')
            ->add('pret', EntityType::class, [
                'class' => Pret::class,
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
            'data_class' => Remboursement::class,
        ]);
    }
}
