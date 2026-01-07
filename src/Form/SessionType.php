<?php

namespace App\Form;

use App\Entity\Session;
use App\Entity\Demande;
use App\Repository\DemandeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        $editMode = $options['edit_mode'] ?? false;

        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Description de la session...'
                ],
            ]);

        // En mode création, pré-remplir la demande si disponible
        if (!$editMode && $user) {
            $builder->add('demande', EntityType::class, [
                'class' => Demande::class,
                'query_builder' => function (DemandeRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('d')
                        ->where('d.statut = :statut')
                        ->setParameter('statut', 'acceptee')
                        ->andWhere('d.demandeur = :user OR d.destinataire = :user')
                        ->setParameter('user', $user);

                    // Pour les non-admins, seulement leurs demandes acceptées sans session
                    if (!$user->getRoles() || !in_array('ROLE_ADMIN', $user->getRoles())) {
                        $qb->leftJoin('d.session', 's')
                            ->andWhere('s.id IS NULL');
                    }

                    return $qb->orderBy('d.dateEnvoi', 'DESC');
                },
                'choice_label' => function(Demande $demande) {
                    $date = $demande->getDateEnvoi()->format('d/m/Y');
                    return sprintf('Demande #%d (%s) - %s',
                        $demande->getId(),
                        $date,
                        $demande->getAnnonce()->getTitre()
                    );
                },
                'label' => 'Demande associée',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez une demande acceptée',
            ]);
        }

        // Champs de date en mode édition
        if ($editMode) {
            $builder
                ->add('dateDebut', DateTimeType::class, [
                    'widget' => 'single_text',
                    'label' => 'Date de début',
                    'attr' => ['class' => 'form-control'],
                ])
                ->add('dateFin', DateTimeType::class, [
                    'widget' => 'single_text',
                    'label' => 'Date de fin',
                    'required' => false,
                    'attr' => ['class' => 'form-control'],
                ]);
        }

        // Statut (en édition ou pour admin)
        if ($editMode || ($user && in_array('ROLE_ADMIN', $user->getRoles()))) {
            $builder->add('statut', ChoiceType::class, [
                'choices' => [
                    'En cours' => 'en_cours',
                    'Terminée' => 'terminee',
                    'Annulée' => 'annulee',
                ],
                'label' => 'Statut',
                'attr' => ['class' => 'form-control'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
            'user' => null,
            'edit_mode' => false,
        ]);

        $resolver->setAllowedTypes('user', ['null', 'App\Entity\Utilisateur']);
        $resolver->setAllowedTypes('edit_mode', 'bool');
    }
}
