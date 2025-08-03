<?php

namespace App\Controller;

use App\Form\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(): Response
    {
        $form = $this->createForm(LoginType::class);
        
        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
            'loginForm' => $form,
        ]);
    }
}
