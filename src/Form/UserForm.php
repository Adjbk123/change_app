<?php

namespace App\Form;

use App\Entity\Agence;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class UserForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
           $builder
               ->add('email', null, [
                   'label' => 'Adresse e-mail',
               ])
               ->add('nom', null, [
                   'label' => 'Nom',
               ])
               ->add('prenoms', null, [
                   'label' => 'Prénoms',
               ])
               ->add('telephone', null, [
                   'label' => 'Numéro de téléphone',
               ])
               ->add('avatar', FileType::class, [
                   'label' => 'Photo de profil (JPG, PNG)',
                   'mapped' => false,
                   'required' => false,
                   'attr' => ['accept' => 'image/*']
               ]);


        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
