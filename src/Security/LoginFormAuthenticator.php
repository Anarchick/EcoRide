<?php

namespace App\Security;

use App\Repository\UserBanRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * @see https://symfony.com/doc/current/security/custom_authenticator.html
 */
class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{


    public function __construct(
        private UserRepository $userRepository,
        private UserBanRepository $userBanRepository,
        private RouterInterface $router
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        // Get data from the login form
        /** @var array $allData */
        $allData = $request->getPayload()->all();
        /** @var array $loginData */
        $loginData = $allData['login'] ?? [];
        /** @var string $email */
        $email = $loginData['email'] ?? '';
        /** @var string $password */
        $password = $loginData['password'] ?? '';
        /** @var string $csrfToken */
        $csrfToken = $loginData['_token'] ?? '';
        /** @var bool $rememberMe */
        $rememberMe = $loginData['remember_me'] ?? false;

        // store the email in the session for the "remember me" feature
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        $passport = new Passport(
            new UserBadge($email, function ($userIdentifier) {
                $user = $this->userRepository->findOneByEmail($userIdentifier);

                if ($user && $user->isBanned()) {
                    $reason = $this->userBanRepository->findOneBy(['user' => $user])?->getReason();
                    throw new CustomUserMessageAuthenticationException(
                        'Votre compte a été banni.' . PHP_EOL .
                        'Veuillez contacter le support pour plus d\'informations.' . PHP_EOL .
                        'Raison du bannissement : ' . ($reason ?? 'Non spécifiée.')
                    );
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $csrfToken)]
        );

        if ($rememberMe) {
            $passport->addBadge(new RememberMeBadge());
        }

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetPath = $request->getSession()->get('_security.' . $firewallName . '.target_path');
    
        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_travel_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('app_login');
    }
    
}
