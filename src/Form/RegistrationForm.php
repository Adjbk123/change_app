<?php

namespace App\Form;

use App\Entity\Agence;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType; // Added for date_debut
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('prenoms', TextType::class,[
                'label' => 'Prénoms',
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
            ])
            ->add('agence', EntityType::class, [
                'label' => 'Agence d\'affectation', // More descriptive label
                'class' => Agence::class,
                'choice_label' => 'nom',
                'attr' => [
                    'class' => 'form-control',
                ],
                'placeholder'=>"-- Choisir une agence --",
                'mapped' => true, // This field will not be directly mapped to the User entity
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une agence.',
                    ]),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle d\'affectation', // More descriptive label
                'choices' => [
                    'Administrateur' => 'ROLE_ADMIN',
                    'Caissier(e)' => 'ROLE_CAISSE',
                    'Responsable d\'agence' => 'ROLE_RESPONSABLE',
                ],
                'mapped'=>false, // This field will not be directly mapped to the User entity
                'attr' => [
                    'class' => 'form-control',
                ],
                'placeholder'=>'-- Choisir un rôle --',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un rôle.',
                    ]),
                ],
            ])
            ->add('date_debut', DateType::class, [
                'label' => 'Date de prise de service',
                'widget' => 'single_text', // Renders as a single input field (e.g., HTML5 date input)
                'html5' => true, // Use HTML5 date input type
                'mapped' => false, // This field will not be directly mapped to the User entity
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer la date de prise de service.',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Veuillez entrer un mot de passe',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                            'max' => 4096,
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les champs du mot de passe doivent correspondre.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
