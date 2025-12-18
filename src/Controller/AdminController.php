<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\DateIntervalEnum;
use App\Enum\RoleEnum;
use App\Form\RegistrationType;
use App\Repository\PlatformCommissionRepository;
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
    #[Route('/', name: 'graphics')]
    public function graphics(TravelRepository $travelRepository, PlatformCommissionRepository $platformCommissionRepository): Response
    {
        $from = new \DateTimeImmutable('-30 days');
        $to = new \DateTimeImmutable('now');

        $chartTravels = $travelRepository->getCountsByPeriod(
            interval: 1,
            intervalEnum: DateIntervalEnum::DAY,
            from: $from,
            to: $to
        );

        $creditsEarned = $platformCommissionRepository->getSumByDay(
            from: $from,
            to: $to
        );

        return $this->render('admin/graphics.html.twig', [
            'chartTravels' => $chartTravels,
            'creditsEarned' => $creditsEarned,
            'totalCredits' => $platformCommissionRepository->getCreditSum(),
            'totalCreditsPeriod' => $platformCommissionRepository->getCreditSum($from, $to),
        ]);
    }

    #[Route('/moderator', name: 'moderators')]
    public function moderator(UserRepository $userRepository): Response
    {
        $moderators = $userRepository->findByRole(RoleEnum::MODERATOR);

        return $this->render('admin/moderators.html.twig', [
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

            return $this->redirectToRoute('app_admin_moderators');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

}
