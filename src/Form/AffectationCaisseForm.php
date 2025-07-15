<?php

namespace App\Form;

use App\Entity\AffectationCaisse;
use App\Entity\Caisse;
use App\Entity\User;
use App\Repository\CaisseRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AffectationCaisseForm extends AbstractType
{
    private $caisseRepository;
    private $userRepository;

    public function __construct(CaisseRepository $caisseRepository, UserRepository $userRepository)
    {
        $this->caisseRepository = $caisseRepository;
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $agence = $options['agence'] ?? null;
        $builder
            ->add('caissier', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn($user) => $user->getNomComplet(),
                'query_builder' => function (UserRepository $ur) use ($agence) {
                    $qb = $ur->createQueryBuilder('u');

                    if ($agence) {
                        $qb->andWhere('u.agence = :agence')
                            ->setParameter('agence', $agence)
                            ->andWhere('u.roles LIKE :role')
                            ->setParameter('role', '%"ROLE_CAISSE"%');
                    } else {
                        $qb->andWhere('1=0'); // Pas d'agence, pas de résultats
                    }

                    return $qb;
                },
                'placeholder' => '-- Sélectionner un caissier --',
            ])

            ->add('caisse', EntityType::class, [
                'class' => Caisse::class,
                'choice_label' => 'nom',
                'query_builder' => function (CaisseRepository $cr) use ($agence) {
                    $qb = $cr->createQueryBuilder('c');
                    if ($agence) {
                        $qb->andWhere('c.agence = :agence')
                            ->setParameter('agence', $agence);
                    } else {
                        $qb->andWhere('1=0');
                    }
                    return $qb;
                },
                'placeholder' => '-- Sélectionner une caisse --',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AffectationCaisse::class,
            'agence' => null,
        ]);
    }
}
