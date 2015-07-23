<?php

namespace SDF\BilletterieBundle\Utils\Ginger\Client;

use Ginger\Client\GingerClient as BaseGingerClient;

/**
 * GingerClient
 * A Ginger client handling its configuration
 *
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
class GingerClient extends BaseGingerClient
{
	protected $gingerKey;
	protected $gingerUrl;

	public function __construct($gingerKey, $gingerUrl)
	{
		$this->gingerKey = $gingerKey;
		$this->gingerUrl = $gingerUrl;
		parent::__construct($this->gingerKey, $this->gingerUrl);

		return $this;
	}
}