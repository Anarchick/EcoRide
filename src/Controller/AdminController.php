<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserBan;
use App\Enum\DateIntervalEnum;
use App\Enum\RoleEnum;
use App\Form\RegistrationType;
use App\Form\UserBanType;
use App\Repository\PlatformCommissionRepository;
use App\Repository\TravelRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
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

    #[Route('/member/{uuid}/ban/', name: 'ban', methods: ['GET', 'POST'], requirements: ['uuid' => Requirement::UID_RFC4122])]
    public function ban(
        Request $request,
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        User $user,
        EntityManagerInterface $em,
    ): Response
    {
        $userBan = new UserBan();
        $userBan->setUser($user);

        $form = $this->createForm(UserBanType::class, $userBan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->addRole(RoleEnum::BANNED);
            $em->persist($user);
            $em->persist($userBan);
            $em->flush();

            $this->addFlash('success', 'L\'utilisateur a été banni.');
            return $this->redirectToRoute('app_member_show', ['uuid' => $user->getUuid()], Response::HTTP_SEE_OTHER);
        }

        return new Response($this->render('admin/ban.html.twig', [
            'form' => $form,
            'user' => $user,
        ]));
    }

    #[Route('/member/{uuid}/unban/', name: 'unban', methods: ['POST'], requirements: ['uuid' => Requirement::UID_RFC4122])]
    public function unban(
        Request $request,
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        User $user,
        EntityManagerInterface $em,
    ): Response
    {
        if ($this->isCsrfTokenValid('unban'.$user->getUuid(), $request->request->get('_token'))) {
            $user->removeRole(RoleEnum::BANNED);

            $userBan = $user->getUserBan();
            if ($userBan) {
                $em->remove($userBan);
            }

            $em->flush();

            $this->addFlash('success', 'L\'utilisateur a été débanni.');
        }

        return $this->redirectToRoute('app_member_show', ['uuid' => $user->getUuid()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/banned_users', name: 'banned_users')]
    public function bannedUsers(
        Request $request,
        UserRepository $userRepository
    ): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $users = $userRepository->findBannedUsers($page);
        $count = $userRepository->countBannedUsers();

        return $this->render('admin/banned_users.html.twig', [
            'users' => $users,
            'count' => $count,
        ]);
    }

}