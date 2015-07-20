<?php

namespace SDF\BilletterieBundle\Authentication\Cas\Provider;

use Exception;
use DateTime;
use DateInterval;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

use Ginger\Client\GingerClient;
use Payutc\Client\JsonException;

use SDF\BilletterieBundle\Authentication\Cas\Token\CasToken;
use SDF\BilletterieBundle\Authentication\Cas\Client\CasClient;
use SDF\BilletterieBundle\Entity\CasUser;
use SDF\BilletterieBundle\Entity\Log;

/**
 * CasProvider
 * Provides a User to authenticate through a token.
 *
 * @author Matthieu Guffroy <mattgu74@gmail.com>
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
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
        // If the token is already authenticated, no need to do it again.
        if ($token->getUser() instanceof User) {
            return $token;
        }

        // Otherwise, use the CasClient to retrieve login information.
        $cas = new CasClient($this->casUrl);

        try {
            $userLogin = $cas->authenticate($token->getTicket(), $token->getService());
        } catch (Exception $e) {
            throw new AuthenticationException(sprintf('The CAS authentication failed (ticket validation): %s, %s', $token->getTicket(), $token->getService()));
        }

        try {
            // Database query in case the user is already registred
            $user = $this->userProvider->loadUserByUsername($userLogin);
        } catch (UsernameNotFoundException $e) {
            // User doesn't already exist, we need to create him an account
            // We will use the GingerClient to retrieve user informations
            $gingerClient = new GingerClient($this->gingerKey, $this->gingerUrl);
            $userInfos = $gingerClient->getUser($userLogin);

            // Create the CasUser instance
            $user = new CasUser();
            $user->setUsername($userLogin);

            // Set informations from Ginger

            // Birthdate : Ginger currently do not return a birthdate, but just a boolean if the user is adult
            // NB: according to french laws, a member is considered as adult if he's more than 18 years old.
            // So we force the birthdate to be 18 years ago.
            $birthdate = new DateTime();
            if ($userInfos->is_adulte) {
                $birthdate->sub(new DateInterval('P18Y'));
            }
            $user->setBirthdate($birthdate);

            $user->setEmail($userInfos->mail);
            $user->setFirstname($userInfos->prenom);
            $user->setName($userInfos->nom);
            $user->setBadge($userInfos->badge_uid);
            $user->setIsBdeContributor($userInfos->is_cotisant);

            // Generate a random password --- It should never be used anyway [CasUser]
            $password = $this->generatePassword(8);
            $user->setPassword($this->passwordEncoder->encodePassword($user, $password));

            // Create Log information
            $log = new Log();
            $log->setUser($user);
            $log->setDate(new DateTime());
            $log->setContent('Création du compte de ' . $user->getUsername());

            $this->entityManager->persist($user);
            $this->entityManager->persist($log);

            $this->entityManager->flush();
        }

        // At this step, we should have one user object now.
        // (Already existing or freshly created)
        if ($user) {
            // Then authenticate it
            $authenticatedToken = new CasToken($user->getRoles());
            $authenticatedToken->setUser($user);

            return $authenticatedToken;
        }

        // If anything fails, throw an exception.
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
