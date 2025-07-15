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
                'choice_label' => function($user) {
                    return $user->getNomComplet();
                },
                'query_builder' => function (UserRepository $ur) use ($agence) {
                    $ids = array_map(function($u) { return $u->getId(); }, $ur->findCaissiersByAgence($agence));
                    $qb = $ur->createQueryBuilder('u');
                    if (count($ids) > 0) {
                        $qb->andWhere($qb->expr()->in('u.id', ':ids'));
                        $qb->setParameter('ids', $ids);
                    } else {
                        $qb->andWhere('1=0'); // Aucun résultat
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
