<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ModoController extends AbstractController
{
    #[Route('/modo', name: 'app_modo')]
    public function index(): Response
    {
        return $this->render('modo/index.html.twig', [
            'controller_name' => 'ModoController',
        ]);
    }
}