<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

// Faster regex UUID
#[Route('/member/{uuid}', name: 'app_member_', requirements: ['uuid' => Requirement::UID_RFC4122])]
final class MemberController extends AbstractController
{
    #[Route('/', name: 'show')]
    public function index(string $uuid, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $userRepository->getByUuid($uuid);
        $sql = 'SELECT * FROM users WHERE uuid = :uuid';
        $conn = $em->getConnection();
        $stmt = $conn->prepare($sql);
        $cryptedUser = $stmt->executeQuery(['uuid' => $user->getUuid()])->fetchAssociative();

        return $this->render('member/index.html.twig', [
            'user' => $user,
            'cryptedUser' => $cryptedUser
        ]);
    }
}
