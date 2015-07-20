<?php

namespace SDF\BilletterieBundle\Authentication\Cas\Provider;

use Exception;
use DateTime;
use DateInterval;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

use Ginger\Client\GingerClient;
use Payutc\Client\JsonException;

use SDF\BilletterieBundle\Authentication\Cas\Token\CasToken;
use SDF\BilletterieBundle\Authentication\Cas\Client\CasClient;
use SDF\BilletterieBundle\Entity\CasUser;
use SDF\BilletterieBundle\Entity\Log;

class CasProvider implements AuthenticationProviderInterface
{
    private $userProvider;
    private $cacheDir;
    private $entityManager;
    private $passwordEncoder;
    private $casUrl;
    private $gingerUrl;
    private $gingerKey;

    public function __construct(UserProviderInterface $userProvider, $cacheDir, $entityManager, $passwordEncoder, $casUrl, $gingerUrl, $gingerKey)
    {
        $this->userProvider     = $userProvider;
        $this->cacheDir         = $cacheDir;
        $this->entityManager    = $entityManager;
        $this->passwordEncoder  = $passwordEncoder;
        $this->casUrl           = $casUrl;
        $this->gingerUrl        = $gingerUrl;
        $this->gingerKey        = $gingerKey;

        return $this;
    }

    public function authenticate(TokenInterface $token)
    {
        if ($token->getUser() instanceof User) {
            return $token;
        }

        $cas = new CasClient($this->casUrl);

        try {
            $userLogin = $cas->authenticate($token->getTicket(), $token->getService());
        } catch (Exception $e) {
            throw new AuthenticationException(sprintf('The CAS authentication failed (ticket validation): %s, %s', $token->getTicket(), $token->getService()));
        }

        $gingerClient = new GingerClient($this->gingerKey, $this->gingerUrl);
        $userInfo = $gingerClient->getUser($userLogin);

        try {
            $user = $this->userProvider->loadUserByUsername($userInfo->mail);
        } catch (UsernameNotFoundException $e) {
            // User doesn't already exist, we need to create him an account
            $birthdate = new DateTime();

            if ($userInfo->is_adulte) {
                $birthdate->sub(new DateInterval('P18Y'));
            }

            $user = new CasUser();
            $user->setUsername($userLogin);
            $user->setBirthdate($birthdate);
            $user->setEmail($userInfo->mail);
            $user->setFirstname($userInfo->prenom);
            $user->setName($userInfo->nom);
            $user->setBadge($userInfo->badge_uid);
            $user->setIsBdeContributor($userInfo->is_cotisant);

            $password = $this->generatePassword(8);
            $user->setPassword($this->passwordEncoder->encodePassword($user, $password));

            $log = new Log();
            $log->setUser($user);
            $log->setDate(new DateTime());
            $log->setContent('Création du compte de ' . $user->getUsername());

            $this->entityManager->persist($user);
            $this->entityManager->persist($log);

            $this->entityManager->flush();
        }

        if ($user) {
            $authenticatedToken = new CasToken($user->getRoles());
            $authenticatedToken->setUser($user);

            return $authenticatedToken;
        }

        throw new AuthenticationException('The CAS authentication failed.');
    }

    /**
     * Generate a randomly generated password based on a php's str_shuffle method called on a characters list.
     *
     * @param int $length
     * @return string
     */
    protected function generatePassword($length = 10)
    {
        // str_shuffle gives one in all possible permutations of the shuffled string
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz-0123456789_ABCDEFGHIJKLMNOPQRSTUVWXYZ!@#+=,;:.$'), 0, $length);
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof CasToken;
    }
}
