<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\Travel;
use App\Entity\TravelPreference;
use App\Entity\User;
use App\Enum\LuggageSizeEnum;
use App\Enum\RoleEnum;
use App\Repository\TravelRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
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
    public function index(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if(!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/travel_history', name: 'travel_history')]
    public function travelHistory(
        Request $request,
        #[CurrentUser] User $user,
        TravelRepository $travelRepository
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

    #[Route('/credits/buy', name: 'credits_buy')]
    public function creditsBuy(
    ): Response
    {
        throw new RuntimeException('Not implemented yet');
    }

    #[Route('/credits/history', name: 'credits_history')]
    public function creditsHistory(
    ): Response
    {
        throw new RuntimeException('Not implemented yet');
    }

    #[Route('/action', name: 'action')]
    public function action(
        Request $request,
        EntityManagerInterface $em,
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $action = $request->request->get('action');

        switch ($action) {
            case 'become_driver':
                if ($this->isGranted(RoleEnum::DRIVER->value)) {
                    $this->addFlash('info', 'Vous êtes déjà conducteur.');
                    return $this->redirectToRoute('app_profile_index');
                }
                return $this->redirectToRoute('app_car_new');
                break;
            case 'create_trip':
                if (!$this->isGranted(RoleEnum::DRIVER->value)) {
                    $this->addFlash('error', 'Vous devez être conducteur pour créer un trajet.');
                    return $this->redirectToRoute('app_profile_index');
                }

                if ($user->getCars()->count() == 0) {
                    $this->addFlash('error', 'Vous devez posséder une voiture pour créer un trajet.');
                    return $this->redirectToRoute('app_profile_index');
                }

                /** @var Car $car */
                $car = $user->getCars()->first();
                $travel = (new Travel())
                    ->setCar($car)
                    ->setDriver($user)
                    ->setDeparture('Annecy')
                    ->setArrival('Marseille')
                    ->setDate(new \DateTimeImmutable('+ 1 day'))
                    ->setPassengersMax($car->getTotalSeats() - 1)
                    ->setDuration(60*5)
                    ->setDistance(450)
                    ->setCost(15.0);
                $em->persist($travel);
                $travelPreference = (new TravelPreference())
                    ->setTravel($travel)
                    ->setLuggageSize(LuggageSizeEnum::MEDIUM);
                $em->persist($travelPreference);
                $em->flush();
                $this->addFlash('success', 'Trajet créé Entre Annecy et Marseille demain');
                break;
            case 'logout':
                return $this->redirectToRoute('app_logout');
                break;
            default:
                $this->addFlash('error', 'Action inconnue : ' . $action);
                break;
        }

        $this->addFlash('success', 'Action effectuée : ' . $action);

        return $this->redirectToRoute('app_profile_index');
    }
}
