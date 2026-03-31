<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Formulaire d’inscription utilisateur.
 *
 * Ce formulaire gère :
 * - les informations personnelles (nom, prénom, email)
 * - la saisie du mot de passe en clair (non mappé à l’entité)
 * - la validation de la politique de mot de passe
 * - l’acceptation des CGU
 *
 * Le mot de passe n’est volontairement pas mappé à l’entité User :
 * il est récupéré dans le contrôleur, haché, puis stocké dans la propriété password.
 */
class RegistrationFormType extends AbstractType
{
    /**
     * Construction du formulaire d’inscription.
     *
     * Chaque champ est configuré avec :
     * - un type (TextType, EmailType, PasswordType…)
     * - un label
     * - des contraintes de validation si nécessaire
     * - l’option mapped=false pour les champs non liés à l’entité User
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'required' => false,
            ])
            
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                //mapped false: Le mot de passe n’est pas directement lié à l’entité User.
                // Il sera récupéré dans le contrôleur, haché, puis stocké dans password.
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'invalid_message' => 'Les mots de passe doivent être identiques.',
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez entrer un mot de passe',
                    ),
                    new Length(
                        min: 8,
                        minMessage: 'Votre mot de passe doit comporter au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        max: 4096,
                    ),
                    new Regex(
                        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                        message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.',
                    ),
                ],
            ])
            // CASE CGU(mapped false: non lié à entity user, juste une validation de checkbox)
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter les CGU.',
                    ),
                ],
            ])
        ;
    }

    /**
     * Configuration du form type :
     * - data_class indique que le formulaire hydrate un objet User
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
