<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\RoleEnum;
use App\Repository\ReviewRepository;
use App\Repository\TravelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile', name: 'app_profile_')]
#[IsGranted(RoleEnum::USER->value)]
final class ProfileController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(
        #[CurrentUser] User $user,
        TravelRepository $travelRepository,
        ReviewRepository $reviewRepository,
    ): Response
    {
        if ($user->isDriver()) {
            $travelReadyToStart = $travelRepository->findReadyToStartTravel($user);
        }

        $travelPending = $travelRepository->findPendingTravel($user);
        $travelInProgress = $travelRepository->findInProgressTravel($user);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'isDriver' => $this->isGranted(RoleEnum::DRIVER->value),
            'travelsPending' => $travelPending ?? null,
            'travelReadyToStart' => $travelReadyToStart ?? null,
            'travelInProgress' => $travelInProgress ?? null,
            'travelsWithPendingReviews' => $travelRepository->findTravelsWithPendingReviews($user),
            'reviews' => $reviewRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], 3),
        ]);
    }

    #[Route('/travel_history', name: 'travel_history')]
    public function travelHistory(
        Request $request,
        #[CurrentUser] User $user,
        TravelRepository $travelRepository,
    ): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $travels = $travelRepository->findTravelsInvolvingUser($user, $page);
        $totalTravels = $travelRepository->CountTravelsInvolvingUser($user);

        return $this->render('profile/travels_history/index.html.twig', [
            'user' => $user,
            'isDriver' => $this->isGranted(RoleEnum::DRIVER->value),
            'travels' => $travels,
            'totalTravels' => $totalTravels,
        ]);
    }

}
