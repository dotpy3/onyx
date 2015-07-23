<?php

namespace SDF\BilletterieBundle\Utils\Payutc\Client;

use Payutc\Client\AutoJsonClient;
use Payutc\Client\JsonException;

/**
 * PayUtc Client
 * Send requests to the payutc API
 *
 * @author Matthieu Guffroy <mattgu74@gmail.com>
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
class PayutcClient extends AutoJsonClient
{
	private $apiKey;
	private $session;
	protected $status;

	public function __construct($session, $apiKey, $url, $service)
	{
		$this->apiKey = $apiKey;
		$this->session = $session;
		$cookies = $session->get('payutc_cookies');
		$this->status = $session->get('payutc_status');

		parent::__construct($url, $service, array(), 'Payutc Json PHP Client', $cookies);

		if (!$cookies) {
			$this->connectApp();
			$this->session->set('payutc_cookie', $this->cookies);
		}
	}

	/**
	 * Ensure that Client is authenticated over PayUtc
	 */
	public function connectApp()
	{
		$status = $this->getStatus();

		if (!$status->application) {
			$return = $this->loginApp(array(
				'key' => $this->apiKey
			));
			$this->getStatus(true);
			return $return;
		}

		return true;
	}

	/**
	 * Create a cache for getStatus
	 *
	 * @param boolean $force Force parent::getStatus call instead of reading from cache.
	 * @return boolean $force Force parent::getStatus call instead of reading from cache.
	 */
	public function getStatus($force = false)
	{
		if (!$this->status || $force) {
			$this->status = parent::getStatus();
			$this->session->set('payutc_status', $this->status);

			if (!$this->status->application) {
				$this->connectApp();
			}
		}

		return $this->status;
	}

	/**
	 * Logout user then trigger a status change
	 */
	public function logout()
	{
		$return = parent::logout();
		$this->getStatus();
		return $return;
	}

	/**
	 * Authenticate user through UTC Cas then trigger a status change
	 */
	public function loginCas($ticket, $service)
	{
		$status = $this->getStatus();

		$return = parent::loginCas(array(
			'ticket' => $ticket,
			'service' => $service
		));

		$this->getStatus(true);

		return $return;
	}
}
