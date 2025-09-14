<?php

namespace App\Controller;

use App\Entity\Brand;
use App\Entity\Car;
use App\Entity\Model;
use App\Entity\Travel;
use App\Entity\TravelPreference;
use App\Entity\User;
use App\Enum\ColorEnum;
use App\Enum\FuelTypeEnum;
use App\Enum\LuggageSizeEnum;
use App\Enum\RoleEnum;
use App\Repository\BrandRepository;
use App\Repository\ModelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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

    #[Route('/action', name: 'action')]
    public function action(Request $request,
            EntityManagerInterface $em,
            BrandRepository $brandRepository,
            ModelRepository $modelRepository
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
                $brand = $brandRepository->findOneBy(['name' => 'Tesla']);

                if (!$brand) {
                    $brand = (new Brand())->setName('Tesla');
                    $em->persist($brand);
                    $this->addFlash('success', 'Marque Tesla créée');
                }

                $model = $modelRepository->findOneBy([
                    'brand' => $brand,
                    'name' => 'Model 3'
                ]);

                if (!$model) {
                    $model = (new Model())->setBrand($brand)->setName('Model 3');
                    $em->persist($model);
                    $this->addFlash('success', 'Marque Model 3 créée');
                }

                $car = $user->getCars()->first();

                if (!$car) {
                    $car = (new Car())
                        ->setColor(ColorEnum::GRAY)
                        ->setFuelType(FuelTypeEnum::ELECTRIC)
                        ->setTotalSeats(4)
                        ->setPlate(uniqid())
                        ->setBrand($brand)
                        ->setModel($model);
                    $em->persist($car);
                    $user->addCar($car);
                    $this->addFlash('success', 'Voiture créée et associée');
                }

                if ($this->isGranted(RoleEnum::DRIVER->value)) {
                    $this->addFlash('info', 'Vous êtes déjà conducteur.');
                } else {
                    $user->addRole(RoleEnum::DRIVER);
                    $em->persist($user);
                    $this->addFlash('success', 'Vous êtes maintenant conducteur !');
                }
                
                $em->flush();

                // $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
                // $this->container->get('security.token_storage')->setToken($token);
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
