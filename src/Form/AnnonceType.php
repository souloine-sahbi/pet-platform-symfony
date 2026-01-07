<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\Annonce;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class AnnonceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'annonce',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Adoption chien berger']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'annonce',
                'choices' => [
                    'Adoption' => 'adoption',
                    'Échange' => 'echange',
                    'Garde' => 'garde',
                    'Promenade' => 'promenade',
                    'Autre' => 'autre'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Active' => 'active',
                    'Clôturée' => 'cloturee'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('animals', EntityType::class, [
                'class' => Animal::class,
                'label' => 'Animaux concernés',
                'choice_label' => function(Animal $animal) {
                    return $animal->getNom() . ' (' . $animal->getRace() . ')';
                },
                'query_builder' => function ($repository) use ($user) {
                    return $repository->createQueryBuilder('a')
                        ->where('a.proprietaire = :user')
                        ->setParameter('user', $user);
                },
                'multiple' => true,
                'expanded' => false,
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Annonce::class,
            'user' => null,
        ]);
    }
}
