<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\UserBanRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BannedUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private TokenStorageInterface $tokenStorage,
        private RouterInterface $router,
        private UserBanRepository $userBanRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var User|null */
        $user = $this->security->getUser();
        
        if (!$user || $event->getRequest()->attributes->get('_route') === 'app_logout') {
            return;
        }

        if ($user->isBanned()) {
            // Logout the user
            $this->tokenStorage->setToken(null);
            $reason = $this->userBanRepository->findOneBy(['user' => $user])?->getReason();
            $message = 'Votre compte a été banni.' . PHP_EOL .
                        'Veuillez contacter le support pour plus d\'informations.' . PHP_EOL .
                        'Raison du bannissement : ' . ($reason ?? 'Non spécifiée.');
            
            /** @var FlashBagInterface $flashesBag*/
            $flashesBag = $event->getRequest()->getSession()->getBag('flashes');
            $flashesBag->add('error', $message);
            $event->setResponse(new RedirectResponse($this->router->generate('app_login')));
        }
    }
}
