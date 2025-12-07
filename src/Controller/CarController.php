<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\User;
use App\Enum\RoleEnum;
use App\Form\CarType;
use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/car', name: 'app_car_')]
#[IsGranted(RoleEnum::USER->value)]
class CarController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(#[CurrentUser()] User $user): Response
    {
        return $this->render('profile/car/index.html.twig', [
            'cars' => $user->getCars(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        #[CurrentUser()] User $user,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $car = new Car();
        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $car->setPlate($car->getPlate()); // To trigger plateHash update
            $car->addUser($user);
            $user->addRole(RoleEnum::DRIVER);
            
            $em->persist($car);
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Votre voiture a été ajoutée avec succès !');

            return $this->redirectToRoute('app_car_index');
        }

        return $this->render('profile/car/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted(RoleEnum::DRIVER->value)]
    public function edit(
        Request $request,
        #[CurrentUser()] User $user,
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        Car $car,
        EntityManagerInterface $em
    ): Response
    {
        // Check if user owns this car
        if (!$car->getUser()->contains($user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres voitures.');
        }

        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Votre voiture a été mise à jour!');

            return $this->redirectToRoute('app_car_index');
        }

        return $this->render('profile/car/form.html.twig', [
            'form' => $form,
            'car' => $car,
        ]);
    }

    #[Route('/{uuid}/remove', name: 'remove', methods: ['POST'])]
    #[IsGranted(RoleEnum::DRIVER->value)]
    public function remove(
        Request $request,
        #[CurrentUser()] User $user,
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        Car $car,
        EntityManagerInterface $em,
        CarRepository $carRepository
    ): Response
    {
        // Check if user owns this car
        if (!$car->getUser()->contains($user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres voitures.');
        }

        if ($carRepository->findActiveTravelsFromCar($car)) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer une voiture ayant des trajets en cours.');
            return $this->redirectToRoute('app_car_index');
        }

        // Verify CSRF token
        if ($this->isCsrfTokenValid('remove' . $car->getUuid(), $request->request->get('_token'))) {
            // A car can still exist in travel history, so we soft delete it
            $car->setIsRemoved(true);
            $em->persist($car);

            if (!$user->hasCar()) {
                $user->removeRole(RoleEnum::DRIVER);
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Votre voiture a été supprimée.');
        }

        return $this->redirectToRoute('app_car_index');
    }
}
