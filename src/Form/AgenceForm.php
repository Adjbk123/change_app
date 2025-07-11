<?php

namespace App\Form;

use App\Entity\Agence;
use App\Entity\Entreprise;
use App\Entity\Pays;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgenceForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l’agence',
            ])
            ->add('pays', EntityType::class, [
                'class' => Pays::class,
                'choice_label' => 'nom',
                'label' => 'Pays',
                'placeholder' => 'Choisir un pays',
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone principal',
            ])
            ->add('telephone2', TelType::class, [
                'label' => 'Téléphone secondaire',
                'required' => false,
            ])
            ->add('entreprise', EntityType::class, [
                'class' => Entreprise::class,
                'choice_label' => 'nom',
                'label' => 'Entreprise associée',
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
       ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Agence::class,
        ]);
    }
}
