<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * @see https://symfony.com/doc/current/security/custom_authenticator.html
 */
class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{

    use TargetPathTrait;

    public function __construct(
        private UserRepository $userRepository,
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
                return $this->userRepository->findOneByEmail($userIdentifier);
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
        if ($target = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($target);
        }

        return new RedirectResponse($this->router->generate('app_travel_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('app_login');
    }
    
}
