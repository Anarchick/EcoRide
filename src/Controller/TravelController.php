<?php

namespace App\Controller;

use App\Form\TravelSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TravelController extends AbstractController
{
    #[Route('/travel', name: 'app_travel')]
    public function index(): Response
    {
        $searchForm = $this->createForm(TravelSearchType::class);
        
        return $this->render('travel/index.html.twig', [
            'controller_name' => 'TravelController',
            'search_form' => $searchForm
        ]);
    }
}
