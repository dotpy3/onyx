<?php

namespace SDF\BilletterieBundle\Authentication\Firewall;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

use SDF\BilletterieBundle\Authentication\Cas\Token\CasToken;

/**
 * CasListener
 * Listen for a CAS ticket, and authenticate a CasToken.
 *
 * This class is registred to intercepts a CAS callback before the normal authentication process.
 *
 * @author Matthieu Guffroy <mattgu74@gmail.com>
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
class CasListener implements ListenerInterface
{
	protected $securityContext;
	protected $authenticationManager;
	protected $session;

	public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, Session $session)
	{
		$this->securityContext = $securityContext;
		$this->authenticationManager = $authenticationManager;
		$this->session = $session;

		return $this;
	}

	public function handle(GetResponseEvent $event)
	{
		$request = $event->getRequest();

		$ticket = $request->get('ticket');

		if ($ticket) {
			$token = new CasToken();
			$token->setTicket($ticket);

			$isAdmin = $request->get('admin');

			if ($isAdmin) {
				$token->setIsAdmin(true);
			}

			// Remove the ticket parameters to get the ticket
			$service = $request->getUri();
			$service = preg_replace('/&?\??ticket=[^&]*/', '', $service);

			$token->setService($service);

			try {
				$authToken = $this->authenticationManager->authenticate($token);
				$this->securityContext->setToken($authToken);
			} catch (AuthenticationException $e) {
				// To deny the authentication clear the token.
				// This will redirect to the login page.
				$this->session->getFlashBag()->add('warning', sprintf('Impossible to authenticate. %s', $e->getMessage()));
				$this->securityContext->setToken(null);
			}
		}
	}
}