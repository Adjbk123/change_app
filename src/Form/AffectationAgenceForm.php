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
