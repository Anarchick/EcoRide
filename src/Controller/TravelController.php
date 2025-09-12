<?php

namespace App\Controller;

use App\Entity\Travel;
use App\Form\TravelSearchType;
use App\Repository\TravelRepository;
use App\Sedarch\TravelCriteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/travel', name: 'app_travel_')]
final class TravelController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request, TravelRepository $travelRepository): Response
    {
        $allTravels = $travelRepository->findAll();
        $searchForm = $this->createForm(TravelSearchType::class);

        $searchForm->handleRequest($request);

        $criteria = new TravelCriteria('Paris', 'Lyon', new \DateTime(), 1);
        $travels = $travelRepository->getTravelsByCriteria($criteria, 1);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $data = $searchForm->getData();
            // Perform search logic here
            dd($data);
        }

        return $this->render('travel/index.html.twig', [
            'search_form' => $searchForm,
            'all_travels' => $allTravels,
            'travels' => $travels
        ]);
    }

    #[Route('/overviews', name: 'overviews', methods: ['GET'])]
    public function overviews(TravelRepository $travelRepository): Response
    {
        /** @var Travel $travel */
        $travel = $travelRepository->getByUuid($uuid);

        if (!$travel) {
            throw $this->createNotFoundException('Travel not found');
        }

        return $this->render('travel/overview.html.twig', [
            'travel' => $travel
        ]);
    }
}
