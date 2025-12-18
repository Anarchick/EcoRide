<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
            Request $request,
            UserPasswordHasherInterface $userPasswordHasher,
            EntityManagerInterface $entityManager
    ): Response
    {
        // If already connected
        if ($this->getUser()) {
            return $this->redirectToRoute('app_travel_index');
        }

        $user = new User();
        
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            if ($form->isValid()) {
                /** @var string $plainPassword */
                $plainPassword = $form->get('plainPassword')->getData();

                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Votre compte a été créé avec succès!');

                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez les corriger.');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form
        ]);
    }
}
