<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

// Faster regex UUID
#[Route('/member/{uuid}', name: 'app_member_', requirements: ['uuid' => Requirement::UID_RFC4122])]
final class MemberController extends AbstractController
{
    #[Route('/', name: 'show')]
    public function index(
        #[MapEntity(mapping: ['uuid' => 'uuid'])]
        ?User $user
    ): Response
    {
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_home');
        }

        if ($user->isBanned()) {
            $this->addFlash('error', "Cet utilisateur est banni et ne peut pas être consulté.");

            if (!$this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('member/index.html.twig', [
            'isBanned' => $user->isBanned(),
            'user' => $user,
        ]);
    }
}
