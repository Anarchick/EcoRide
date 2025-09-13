<?php

namespace App\Controller;

use App\Form\TravelSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $searchForm = $this->createForm(TravelSearchType::class);
        $searchForm->handleRequest($request);

        $requestData = $request->query->all();
        /** @var array|null $data */
        $data = $requestData['travel_search'] ?? [];

        if (!empty($data)) {
            // HTMX fix
            $searchForm->submit($data, false);
        }

        if ($request->headers->get('HX-Request') && $searchForm->isSubmitted() && $searchForm->isValid()) {
            // Redirect to /travel to avoid HTMX swapping
            // This create a real redirection and load the JS of /travel
            $travelUrl = $this->generateUrl('app_travel_index', $request->query->all());
            $response = new Response('', 200);
            $response->headers->set('HX-Redirect', $travelUrl);
            return $response;
        }

        // TODO : test with wrong data, maybe need to real redirect to app_login to avoid htmx swap

        // if ($searchForm->isSubmitted() && $searchForm->isValid()) {
        //     return $this->redirectToRoute('app_travel_index', $requestData);
        // }
        
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'search_form' => $searchForm,
        ]);
    }
}
