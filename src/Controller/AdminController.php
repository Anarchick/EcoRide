<?php

namespace App\Controller;

use App\Enum\DateIntervalEnum;
use App\Enum\RoleEnum;
use App\Repository\TravelRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted(RoleEnum::ADMIN->value)]
final class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_index')]
    public function index(TravelRepository $travelRepository, UserRepository $userRepository): Response
    {
        $chartTravels = $travelRepository->getCountsByPeriod(
            interval: 1,
            intervalEnum: DateIntervalEnum::DAY,
            from: new \DateTimeImmutable('-30 days'),
            to: new \DateTimeImmutable('now')
        );

        return $this->render('admin/index.html.twig', [
            'chartTravels' => $chartTravels,
        ]);
    }
}
