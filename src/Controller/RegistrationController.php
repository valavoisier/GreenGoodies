<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur responsable de l’inscription des utilisateurs.
 *
 * Cette classe gère :
 * - l’affichage du formulaire d’inscription
 * - la validation des données envoyées
 * - le hachage sécurisé du mot de passe
 * - la création et la sauvegarde d’un nouvel utilisateur
 *
 * Symfony utilise ici son système de formulaires pour hydrater automatiquement
 * l’entité User à partir des données envoyées par l’utilisateur.
 */
class RegistrationController extends AbstractController
{
    /**
     * Affiche et traite le formulaire d’inscription.
     *
     * Fonctionnement :
     * - Création d’un nouvel objet User
     * - Génération du formulaire RegistrationFormType lié à cet utilisateur
     * - Hydratation automatique de l’entité via handleRequest()
     * - Vérification de la soumission et de la validité du formulaire
     * - Récupération du mot de passe en clair
     * - Hachage sécurisé via UserPasswordHasherInterface
     * - **Désactivation initiale de l’accès API pour les nouveaux utilisateurs**
     * - Persistance de l’utilisateur en base
     * - Redirection vers la page d’accueil après succès
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // Création d’un nouvel utilisateur vide.
        // Il sera automatiquement rempli par les données du formulaire.
        $user = new User();
        // Création du formulaire d’inscription lié à l’entité User.
        $form = $this->createForm(RegistrationFormType::class, $user);
        // Hydratation automatique de l’entité User avec les données envoyées.
        $form->handleRequest($request);

        // Vérification : formulaire soumis + données valides.
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération du mot de passe en clair depuis le formulaire.
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Hachage sécurisé du mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            // Par défaut, l'accès à l'API est désactivé pour les nouveaux utilisateurs
            $user->setApiAccess(false);

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            // Redirection après inscription réussie.
            return $this->redirectToRoute('app_home');
        }

        // Affichage du formulaire d’inscription.
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
