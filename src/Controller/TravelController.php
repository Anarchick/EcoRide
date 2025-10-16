<?php

namespace App\Controller;

use App\Entity\Travel;
use App\Enum\TravelStateEnum;
use App\Form\TravelSearchType;
use App\Repository\TravelRepository;
use App\Model\Search\TravelCriteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/travel', name: 'app_travel_')]
final class TravelController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, TravelRepository $travelRepository, KernelInterface $kernel): Response
    {
        $htmxHeader = $request->headers->get('HX-Request');
        $htmxTarget = $request->headers->get('HX-Target');
        // templates/components/TravelSearchBar.html.twig
        $isHtmx = isset($htmxHeader) && $htmxTarget === 'travel-results';
        
        $searchForm = $this->createForm(TravelSearchType::class);
        $searchForm->handleRequest($request);

        $requestData = $request->query->all();
        $session = $request->getSession();
        /** @var array|null $data */
        $data = null;
        $travels = [];

        if (!$isHtmx && empty($requestData)) {
            // Pre-fill the search form with session data when navigate 
            $session = $request->getSession();
            $data = $session->get('travel_search_form', []);
        } else if (isset($requestData['travel_search'])) {
            // Pre-fill from $_GET
            $data = $requestData['travel_search'] ?? [];
        }

        if (isset($data['date']) && new \DateTime($data['date']) < new \DateTime('today')) {
            // Clear session if date is yesterday or earlier
            $data = [];
            $session->remove('travel_search_form');
        }

        // HTMX Context = Trigger HTMX,
        // Otherwise, pre-fill the form with SESSION or GET data
        if (!empty($data)) {
            $searchForm->submit($data, false);
        }

        if ($isHtmx && $searchForm->isSubmitted()) {
            // Objectives :
            // - populate $travels
            // - store the search form data in session
            if (!$searchForm->isValid()) {
                $this->addFlash('error', 'Critères de recherche invalides');
            } else {
                try {
                    /** Validated inputs, safe to use
                     * @var TravelCriteria
                     * */
                    $criteria = $searchForm->getData();
                    $page = max((int)$request->query->getInt('page', 1), 1);
                    $travels = $travelRepository->getTravelsByCriteria($criteria, $page);
                    $formData = $request->query->all('travel_search');
                    $session->set('travel_search_form', $formData);
                } catch (\Exception $e) {
                    if ($kernel->getEnvironment() === 'dev') {
                        $this->addFlash('error', 'Erreur lors de la recherche: ' . $e->getMessage());
                    }
                    $this->addFlash('error', 'Il y a eu une erreur lors de la recherche');
                    // TODO log
                }
            }

        }
        
        if ($isHtmx) {
            if (count($travels) === 0) {
                $this->addFlash('info', 'Aucun trajet ne correspond à vos critères de recherche.');
            }

            return $this->render('travel/overviews.html.twig', [
                'travels' => $travels,
                'criteria' => $searchForm->getData(),
            ]);
        }

        // not HTMX Context
        return $this->render('travel/index.html.twig', [
            'search_form' => $searchForm,
            'criteria' => $searchForm->getData(),
        ]);
    }

    #[Route('/index-debug', name: 'index_debug')]
    public function indexDebug(TravelRepository $travelRepository, KernelInterface $kernel): Response
    {
        if ($kernel->getEnvironment() !== 'dev') {
            throw $this->createNotFoundException('Page non trouvée');
        }

        $criteria = new TravelCriteria();
        $criteria->date = new \DateTime('tomorrow');
        $criteria->departure = 'Paris';
        $criteria->arrival = 'Lyon';
        $criteria->minPassengers = 1;
        $criteria->maxCost = 900;

        $travels = $travelRepository->getTravelsByCriteria($criteria, 1);
        dd($travels);
        
    }

    #[Route('/{uuid32}', name: 'show', methods: ['GET'], requirements: ['uuid32' => '[a-f0-9]{32}'])]
    public function show(Request $request, string $uuid32, TravelRepository $travelRepository): Response
    {
        /** @var Travel|null */
        $travel = $travelRepository->getByUuid($uuid32);
        $requestData = $request->query->all();
        $slot = $requestData['slot'] ?? 1;

        if (!$travel) {
            throw $this->createNotFoundException('Trajet non trouvé');
        }

        if ($travel->getState() !== TravelStateEnum::PENDING) {
            $this->addFlash('error', 'Ce trajet n\'est plus disponible pour la réservation.');
            return $this->redirectToRoute('app_travel_index');
        }

        $carpoolers = $travel->getCarpoolers();
        $slot = $travel->getValidatedSlotCount($slot);
        
        return $this->render('travel/show.html.twig', [
            'travel' => $travel,
            'arrivalDateTime' => (new \DateTime($travel->getDate()->format('Y-m-d H:i:s')))->add(new \DateInterval('PT' . $travel->getDuration() . 'M')),
            'carpoolers' => $carpoolers,
            'slot' => $slot,
        ]);
    }

    #[Route('/{uuid32}/book', name: 'book', methods: ['POST'], requirements: ['uuid32' => '[a-f0-9]{32}'])]
    public function book(Request $request, string $uuid32, TravelRepository $travelRepository): Response
    {   
        // Redirect to booking confirmation page (to be created)
        return $this->redirectToRoute('app_travel_show', [
            'uuid32' => $uuid32
        ]);
    }

}
