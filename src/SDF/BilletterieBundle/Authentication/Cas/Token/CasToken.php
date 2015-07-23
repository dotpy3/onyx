<?php

namespace SDF\BilletterieBundle\Authentication\Cas\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * CasToken
 * Authentication Token model for CAS users
 *
 * @author Matthieu Guffroy <mattgu74@gmail.com>
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
class CasToken extends AbstractToken
{
	protected $ticket = '';
	protected $service = '';
	protected $isAdmin = false;

	public function __construct(array $roles = array())
	{
		parent::__construct($roles);

		// Si l'utilisateur a des rôles, on le considère comme authentifié
		$this->setAuthenticated(count($roles) > 0);
	}

	public function getCredentials()
	{
		return '';
	}

	public function setTicket($ticket)
	{
		$this->ticket = $ticket;

		return $this;
	}

	public function getTicket()
	{
		return $this->ticket;
	}

	public function setService($service)
	{
		$this->service = $service;

		return $this;
	}

	public function getService()
	{
		return $this->service;
	}

	public function setIsAdmin($isAdmin)
	{
		$this->isAdmin = $isAdmin;

		return $this;
	}

	public function getIsAdmin()
	{
		return $this->isAdmin;
	}
}
