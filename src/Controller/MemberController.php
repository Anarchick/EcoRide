<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
// Faster regex UUID
#[Route('/member/{slug}', name: 'app_member_', requirements: ['slug' => '[a-f0-9]{32}'])]
final class MemberController extends AbstractController
{
    #[Route('/', name: 'show')]
    public function index(string $slug, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $userRepository->getUserByUuid($slug);
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
