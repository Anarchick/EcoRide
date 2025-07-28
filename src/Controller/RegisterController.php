<?php

namespace App\Controller;

use App\Form\RegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function index(): Response
    {
        $form = $this->createForm(RegistrationType::class);
        
        return $this->render('register/index.html.twig', [
            'controller_name' => 'RegisterController',
            'registrationForm' => $form,
        ]);
    }
}
