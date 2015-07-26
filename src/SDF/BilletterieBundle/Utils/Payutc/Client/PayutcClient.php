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
	private $systemId;
	protected $status;

	public function __construct($session, $apiKey, $url, $service, $systemId)
	{
		$this->apiKey = $apiKey;
		$this->session = $session;
		$this->systemId = $systemId;
		$cookies = $session->get('payutc_cookies');
		$this->status = $session->get('payutc_status');

		parent::__construct($url, $service, array(), 'Payutc Json PHP Client', $cookies, $systemId, $this->apiKey);

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
}
