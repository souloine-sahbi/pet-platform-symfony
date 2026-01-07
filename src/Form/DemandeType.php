<?php

namespace App\Form;

use App\Entity\Annonce; // ADDED
use App\Entity\Demande;
use Doctrine\ORM\EntityRepository; // ADDED
use Symfony\Bridge\Doctrine\Form\Type\EntityType; // ADDED
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType; // ADDED
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $annonce = $options['annonce'] ?? null;

        $builder
            ->add('message', TextareaType::class, [
                'label' => 'Votre message',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => $annonce ?
                        'Ex: Je suis intéressé par votre annonce "' . $annonce->getTitre() . '"...' :
                        'Expliquez votre demande...'
                ]
            ]);

        // Si une annonce est spécifiée, cacher le champ annonce
        if ($annonce) {
            $builder->add('annonce', HiddenType::class, [
                'mapped' => false,
                'data' => $annonce->getId(),
            ]);
        } else {
            // Sinon, laisser choisir l'annonce
            $builder->add('annonce', EntityType::class, [
                'class' => Annonce::class,
                'label' => 'Annonce concernée',
                'choice_label' => function (Annonce $annonce) {
                    return $annonce->getTitre() . ' (' . $annonce->getType() . ')';
                },
                'query_builder' => function (EntityRepository $er) {
                    // Logique pour filtrer les annonces disponibles
                    return $er->createQueryBuilder('a')
                        ->where('a.statut = :statut')
                        ->setParameter('statut', 'active')
                        ->orderBy('a.titre', 'ASC');
                },
                'attr' => ['class' => 'form-control'],
                'required' => true
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Demande::class,
            'annonce' => null,
        ]);
    }
}
