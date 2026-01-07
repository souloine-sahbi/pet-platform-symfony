<?php

namespace App\Form;

use App\Entity\Message;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        $editMode = $options['edit_mode'] ?? false;

        $builder->add('contenu', TextareaType::class, [
            'label' => 'Message',
            'attr' => [
                'class' => 'form-control',
                'rows' => 8,
                'placeholder' => 'Écrivez votre message ici...'
            ],
        ]);

        // Seuls les admins peuvent choisir l'expéditeur
        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            $builder->add('expediteur', EntityType::class, [
                'class' => Utilisateur::class,
                'query_builder' => function (UtilisateurRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.nom', 'ASC');
                },
                'choice_label' => function (Utilisateur $user) {
                    return $user->getNom() . ' ' . $user->getPrenom() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Expéditeur',
                'attr' => ['class' => 'form-control'],
            ]);
        }

        // Le destinataire est requis pour tout le monde (sauf en mode édition)
        if (!$editMode) {
            $builder->add('destinataire', EntityType::class, [
                'class' => Utilisateur::class,
                'query_builder' => function (UtilisateurRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('u')
                        ->where('u.id != :user')
                        ->setParameter('user', $user ? $user->getId() : 0)
                        ->orderBy('u.nom', 'ASC');

                    // Pour les non-admins, permettre de contacter les autres clients ET les admins
                    if ($user && !in_array('ROLE_ADMIN', $user->getRoles())) {
                        $qb->andWhere('u INSTANCE OF App\Entity\Client OR u INSTANCE OF App\Entity\Admin');
                    }

                    return $qb;
                },
                'choice_label' => function (Utilisateur $user) {
                    return $user->getNom() . ' ' . $user->getPrenom() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Destinataire',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez un destinataire',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
            'user' => null,
            'edit_mode' => false,
        ]);

        $resolver->setAllowedTypes('user', ['null', 'App\Entity\Utilisateur']);
        $resolver->setAllowedTypes('edit_mode', 'bool');
    }
}
