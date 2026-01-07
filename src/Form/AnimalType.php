<?php

namespace App\Form;

use App\Entity\Animal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class AnimalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'animal',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Max']
            ])
            ->add('race', TextType::class, [
                'label' => 'Race/Espèce',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Berger Allemand']
            ])
            ->add('age', IntegerType::class, [
                'label' => 'Âge (années)',
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Mâle' => 'male',
                    'Femelle' => 'femelle'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de l\'animal',
                'required' => false,
                'mapped' => false, // IMPORTANT : pas mappé directement à l'entité
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF ou WebP)',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Description, caractère, etc.']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Animal::class,
        ]);
    }
}
