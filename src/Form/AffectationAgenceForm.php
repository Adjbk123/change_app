<?php

namespace App\Form;

use App\Entity\AffectationAgence;
use App\Entity\Agence;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AffectationAgenceForm extends AbstractType
{
    private $userRepository;
    private $agence;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $agence = $options['agence'];
        $builder
            ->add('roleInterne')
            ->add('dateDebut')
            ->add('dateFin')
            ->add('actif')
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function($user) {
                    return $user->getNomComplet();
                },
                'query_builder' => function (UserRepository $ur) use ($agence) {
                    return $ur->createQueryBuilder('u')
                        ->andWhere('u.agence = :agence')
                        ->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
                        ->setParameter('agence', $agence)
                        ->setParameter('role', '"ROLE_CAISSE"');
                },
                'placeholder' => '-- Sélectionner un caissier --',
            ])
            ->add('agence', EntityType::class, [
                'class' => Agence::class,
                'choice_label' => 'nom',
                'choices' => $agence ? [$agence] : [],
                'disabled' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AffectationAgence::class,
            'agence' => null,
        ]);
    }
}
