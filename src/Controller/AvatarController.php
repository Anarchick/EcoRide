<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AvatarUploadType;
use App\Service\CloudinaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile/avatar')]
#[IsGranted('ROLE_USER')]
class AvatarController extends AbstractController
{
    #[Route('/upload', name: 'app_avatar_upload', methods: ['GET', 'POST'])]
    public function upload(
        Request $request,
        CloudinaryService $cloudinary,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(AvatarUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatar')->getData();

            if ($avatarFile) {
                try {
                    // Upload to Cloudinary
                    $avatarUrl = $cloudinary->uploadAvatar(
                        $avatarFile,
                        $user->getUuid()
                    );

                    // Save URL in database
                    $user->setAvatarUrl($avatarUrl);
                    $entityManager->flush();

                    $this->addFlash('success', 'Votre photo de profil a été mise à jour avec succès.');

                    return $this->redirectToRoute('app_profile_index');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de votre photo.');
                }
            }
        }

        return $this->render('profile/avatar_upload.html.twig', [
            'form' => $form,
            'current_avatar' => $user->getAvatarUrl(),
        ]);
    }

    #[Route('/delete', name: 'app_avatar_delete', methods: ['POST'])]
    public function delete(
        CloudinaryService $cloudinary,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getAvatarUrl()) {
            try {
                // Delete from Cloudinary
                $cloudinary->deleteAvatar($user->getUuid());

                // Remove URL from database
                $user->setAvatarUrl(null);
                $entityManager->flush();

                $this->addFlash('success', 'Votre photo de profil a été supprimée.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression.');
            }
        }

        return $this->redirectToRoute('app_profile_show');
    }
}
