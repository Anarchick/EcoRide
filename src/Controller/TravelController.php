<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\Review;
use App\Entity\Travel;
use App\Entity\User;
use App\Enum\RoleEnum;
use App\Enum\TravelStateEnum;
use App\Form\Travel2Type;
use App\Form\TravelBookType;
use App\Form\TravelCompleteType;
use App\Form\TravelType;
use App\Form\TravelSearchType;
use App\Repository\TravelRepository;
use App\Model\Search\TravelCriteria;
use App\Repository\CarRepository;
use App\Repository\ReviewRepository;
use App\Security\Voter\TravelVoter;
use App\Service\MapService;
use App\Service\SessionTTLService;
use App\Service\TravelCreationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
                $nearestDate = $travelRepository->getNearestTravelDateByCriteria($searchForm->getData());
                if ($nearestDate) {
                    $this->addFlash('info', 'Aucun trajet ne correspond à vos critères de recherche. Le trajet le plus proche est le ' . $nearestDate->format('d/m/Y'));
                } else {
                    $this->addFlash('info', 'Aucun trajet ne correspond à vos critères de recherche.');
                }
            }

            /** @var TravelCriteria */
            $criteria = $searchForm->getData();

            return $this->render('travel/overviews.html.twig', [
                'travels' => $travels,
                'criteria' => $criteria,
                'totalTravels' => $travelRepository->countTravelsByCriteria($criteria),
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

    #[Route('/create', name: 'create')]
    #[IsGranted(RoleEnum::DRIVER->value)]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        CarRepository $carRepository,
        MapService $mapService,
        TravelCreationService $travelCreationService,
        SessionTTLService $sessionTTL,
    ): Response
    {
        /** @var User|null */
        $user = $this->getUser();

        if ($user->getCars()->isEmpty()) {
            $this->addFlash('error', 'Vous devez ajouter une voiture avant de pouvoir créer un trajet.');
            return $this->redirectToRoute('app_car_create');
        }
        /** @var array<string, mixed>|null */
        $travelData = $sessionTTL->get('travel_create_data');

        // Step 1 : Needed data for step 2
        if (!$travelData ) {
            $travel = new Travel();
            $form = $this->createForm(TravelType::class, $travel, ['cars' => $user->getCars()]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $result = $travelCreationService->validateStep1($travel);

                if (!$result['success']) {
                    $this->addFlash('error', $result['error']);
                    return $this->redirectToRoute('app_travel_create');
                }

                $sessionTTL->set('travel_create_data', [
                    ...$result['data'],
                    'date' => $travel->getDate()->format('Y-m-d H:i:s'),
                    'car_uuid' => $travel->getCar()->getUuid()->toRfc4122(),
                ], 1800);

                return $this->redirectToRoute('app_travel_create');
            }

            return $this->render('travel/create.html.twig', [
                'form' => $form,
            ]);
        }

        // Step 2
        /** @var Car|null */
        $car = $carRepository->getByUuid($travelData['car_uuid']);

        if (!$car) {
            $sessionTTL->remove('travel_create_data');
            $this->addFlash('error', 'voiture invalide.');
            return $this->redirectToRoute('app_travel_create');
        }

        $travel = $travelCreationService->createTravel(
            $user,
            $car,
            $travelData,
            new \DateTimeImmutable($travelData['date'])
        );

        $form = $this->createForm(Travel2Type::class, $travel, [
            'passengers_max' => $travel->getPassengersMax(),
            'distance_km' => $travel->getDistance(),
            'fuel_type' => $car->getFuelType(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($travel);
            $em->persist($travel->getTravelPreference());
            $em->flush();

            $sessionTTL->remove('travel_create_data');

            $this->addFlash('success', 'Trajet créé.');
            return $this->redirectToRoute('app_travel_show', [
                'uuid' => $travel->getUuid()->toRfc4122(),
            ], Response::HTTP_SEE_OTHER);
        }

        $map = $mapService->createTravelMap(
            $travelData['departure'],
            $travelData['arrival']
        );

        return $this->render('travel/create_step2.html.twig', [
            'form' => $form,
            'map' => $map,
        ]);
    }

    #[Route('/{uuid}/cancel', name: 'cancel', methods: ['POST'], requirements: ['uuid' => Requirement::UID_RFC4122])]
    #[IsGranted(TravelVoter::EDIT, subject: 'travel')]
    public function cancel(
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        ?Travel $travel,
        EntityManagerInterface $em
    ): Response
    {
        if (!$travel->isCancellable()) {
            $this->addFlash('error', 'Le trajet ne peut pas être annulé.');
            return $this->redirectToRoute('app_home');
        }

        foreach ($travel->getCarpoolers()->toArray() as $carpooler) {
            $travel->removeCarpooler($carpooler);
            $em->remove($carpooler);
        }

        $travel->setState(TravelStateEnum::CANCELLED);
        $em->flush();

        $this->addFlash('success', 'Trajet annulé');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/{uuid}/remove', name: 'remove', methods: ['POST'],
        requirements: ['uuid' => Requirement::UID_RFC4122]
    )]
    #[IsGranted(TravelVoter::REMOVE, subject: 'travel')]
    public function remove(
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        Travel $travel,
        EntityManagerInterface $entityManager
    ): Response
    {
        $entityManager->remove($travel);
        $entityManager->flush();

        $this->addFlash('success', 'Trajet supprimé.');
        return $this->redirectToRoute('app_travel_index', [],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/{uuid}', name: 'show', methods: ['GET'],
        requirements: ['uuid' => Requirement::UID_RFC4122]
    )]
    public function show(
        Request $request,
        String $uuid,
        TravelRepository $travelRepository,
        ReviewRepository $reviewRepository,
        MapService $mapService,
    ): Response
    {
        /** @var Travel|null */
        $travel = $travelRepository->getByUuid($uuid);

        if (!$travel) {
            $this->addFlash('error', 'Trajet non trouvé');
            return $this->redirectToRoute('app_travel_index');
        }

        if ($travel->getState() !== TravelStateEnum::PENDING) {
            $this->addFlash('warning', 'Ce trajet n\'est plus disponible pour la réservation.');
        }

        $requestData = $request->query->all();
        $slot = $travel->getValidatedSlotCount($requestData['slot'] ?? 1);
        
        // Create map with Symfony UX Map
        $map = $mapService->createTravelMap(
            $travel->getDeparture(),
            $travel->getArrival()
        );
        
        return $this->render('travel/show.html.twig', [
            'travel' => $travel,
            'driverReviewsCount' => $reviewRepository->count(['user' => $travel->getDriver()]),
            'driverReviews' => $reviewRepository->findBy(['user' => $travel->getDriver()], ['createdAt' => 'DESC'], 3),
            'slot' => $slot,
            'isCarpooler' => $travel->isCarpooler($this->getUser()),
            'isDriver' => $travel->getDriver() === $this->getUser(),
            'map' => $map, // Pass map to template
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit',
        requirements: ['uuid' => Requirement::UID_RFC4122]
    )]
    #[IsGranted(TravelVoter::EDIT, subject: 'travel')]
    public function edit(
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        Travel $travel,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $form = $this->createForm(Travel2Type::class, $travel, [
            'passengers_max' => $travel->getPassengersMax(),
            'distance_km' => $travel->getDistance(),
            'fuel_type' => $travel->getCar()->getFuelType(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Trajet mis à jour.');

            return $this->redirectToRoute('app_travel_show', [
                'uuid' => $travel->getUuid()->toRfc4122(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('travel/edit.html.twig', [
            'form' => $form,
            'travel' => $travel,
        ]);
    }

    #[Route('/{uuid}/book', name: 'book', methods: ['GET', 'POST'], requirements: ['uuid' => Requirement::UID_RFC4122])]
    #[IsGranted(RoleEnum::USER->value)]
    public function book(
        Request $request,
        String $uuid,
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        ?Travel $travel,
        #[CurrentUser] User $user,
        EntityManagerInterface $em
    ): Response
    {
        if (!$travel) {
            $this->addFlash('error', 'Trajet non trouvé');
            return $this->redirectToRoute('app_travel_index');
        }

        if ($travel->getState() !== TravelStateEnum::PENDING) {
            $this->addFlash('error', 'Ce trajet n\'est plus disponible pour la réservation.');
            return $this->redirectToRoute('app_travel_index');
        }

        if ($travel->isCarpooler($user)) {
            $this->addFlash('error', 'Vous avez déjà réservé ce trajet.');
            return $this->redirectToRoute('app_travel_show', ['uuid' => $uuid]);
        }

        $form = $this->createForm(TravelBookType::class, null, [
            'default_slot' => $request->query->getInt('slot', 1),
            'max_slot' => $travel->getAvailableSlots(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())  {
            $slot = $form->get('slots')->getData();
            $cost = $slot * $travel->getCost();

            try {
                $carpool = $travel->join($user, $slot, $cost);
                
                if ($carpool) {
                    if ($travel->getAvailableSlots() <= 0) {
                        $travel->setState(TravelStateEnum::FULL);
                    }
                    $em->persist($carpool);
                    $em->flush();
                }

                $this->addFlash('success', "Réservation de $slot place(s) effectuée.");
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la réservation. Veuillez réessayer plus tard.');
                // TODO log
            }

            return $this->redirectToRoute('app_travel_show', ['uuid' => $uuid, 'slot' => $slot]);
        }
        
        return $this->render('travel/book.html.twig', [
            'travel' => $travel,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/unbook', name: 'unbook', methods: ['POST'], requirements: ['uuid' => Requirement::UID_RFC4122])]
    #[IsGranted(TravelVoter::UNBOOK, subject: 'travel')]
    public function unbook(
        String $uuid,
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        ?Travel $travel,
        #[CurrentUser] User $user,
        EntityManagerInterface $em
    ): Response
    {
        $carpooler = $travel->removeCarpooler($user);
        $em->remove($carpooler);
        $travel->setState(TravelStateEnum::PENDING);
        $em->flush();

        $this->addFlash('success', 'Réservation annulée');
        return $this->redirectToRoute('app_travel_show', ['uuid' => $uuid]);
    }

    #[Route('/{uuid}/start', name: 'start', methods: ['POST'], requirements: ['uuid' => Requirement::UID_RFC4122])]
    #[IsGranted(TravelVoter::START, subject: 'travel')]
    public function start(
        Request $request,
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        ?Travel $travel,
        EntityManagerInterface $em
    ): Response
    {
        $travel->setState(TravelStateEnum::IN_PROGRESS);
        $em->flush();

        $this->addFlash('success', 'Trajet démarré');
        return $this->redirect($request->getUri());
    }

    #[Route('/{uuid}/complete', name: 'complete', methods: ['GET', 'POST'], requirements: ['uuid' => Requirement::UID_RFC4122])]
    #[IsGranted(TravelVoter::COMPLETE, subject: 'travel')]
    public function complete(
        Request $request,
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        ?Travel $travel,
        #[CurrentUser] User $driver,
        EntityManagerInterface $em
    ): Response
    {
        if ($travel->getState() !== TravelStateEnum::IN_PROGRESS) {
            $this->addFlash('error', 'Le trajet doit être en cours pour le valider.');
            return $this->redirectToRoute('app_profile_index');
        }

        $carpoolers = $travel->getCarpoolers();

        if ($carpoolers->count() === 0) {
            $travel->setState(TravelStateEnum::COMPLETED);
            $em->flush();

            $this->addFlash('success', 'Trajet terminé');
            return $this->redirectToRoute('app_profile_index');
        }

        $formData = ['reviews' => []];
        
        foreach ($carpoolers as $carpooler) {
            $review = new Review();
            $review->setAuthor($driver);
            $review->setUser($carpooler->getUser());
            $review->setTravel($travel);
            $formData['reviews'][] = $review;
        }

        $form = $this->createForm(TravelCompleteType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            foreach ($data['reviews'] as $review) {
                $em->persist($review);
            }

            $travel->setState(TravelStateEnum::COMPLETED);
            $em->flush();

            $this->addFlash('success', 'Trajet terminé. Les covoitureurs peuvent maintenant vous laisser un avis.');
            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render('travel/complete.html.twig', [
            'travel' => $travel,
            'form' => $form,
        ]);
    }
    
}