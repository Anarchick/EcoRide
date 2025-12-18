<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\DateIntervalEnum;
use App\Enum\RoleEnum;
use App\Form\RegistrationType;
use App\Repository\TravelRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted(RoleEnum::ADMIN->value)]
final class AdminController extends AbstractController
{
    #[Route('/', name: 'index')]
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

    #[Route('/moderator', name: 'moderator_index')]
    public function moderator(UserRepository $userRepository): Response
    {
        $moderators = $userRepository->findByRole(RoleEnum::MODERATOR);

        return $this->render('admin/moderator/index.html.twig', [
            'moderators' => $moderators,
        ]);
    }

    #[Route('/moderator/new', name: 'moderator_new')]
    public function moderatorNew(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $userPasswordHasher,
    ): Response
    {
        $user = new User();
        
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->addRole(RoleEnum::MODERATOR);
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Le modérateur a été créé.');

            return $this->redirectToRoute('app_admin_moderator_index');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
