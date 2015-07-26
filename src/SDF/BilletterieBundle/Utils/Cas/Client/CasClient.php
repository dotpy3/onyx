<?php

namespace SDF\BilletterieBundle\Utils\Cas\Client;

use Exception;
use InvalidArgumentException;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector\CssSelector;

use GuzzleHttp\Client as HttpClient;

/**
 * CasClient
 * Listen for a CAS ticket, and authenticate a CasToken.
 *
 * This class is registred to intercepts a CAS callback before the normal authentication process.
 *
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
class CasClient extends HttpClient
{
	protected $logger;

	public function __construct($logger, $url, $timeout = 10)
	{
		parent::__construct(array(
			'base_uri' => $url,
			'timeout' => $timeout
		));

		$this->logger = $logger;

		return $this;
	}

	public function authenticate($ticket, $service)
	{
		$response = $this->request('GET', $this->getValidationUrl($ticket, $service));

		$content = $response->getBody()->getContents();
		$content = str_replace('\n', '', $content);

		CssSelector::disableHtmlExtension();
		$crawler = new Crawler();
		$crawler->addXmlContent($content);

		try {
			$login = $crawler->filter('cas|user')->text();
			$this->logger->info(sprintf('Successfully authenticated %s from CAS.', $login));
		}
		catch (InvalidArgumentException $e) {
			try {
				$code = $crawler->filter('cas|authenticationfailure')->extract('code')[0];
				$this->logger->warning(sprintf('CAS Authentication failure with code [%s].', $code));
				throw new Exception($code);
			}
			catch (InvalidArgumentException $e) {
				$this->logger->warning(sprintf('Impossible to parse CAS return with ticket [%s] and service [%s].', $ticket, $service));
				throw new Exception('Impossible to parse CAS content.');
			}
		}

		return $login;
	}

	protected function getLoginUrl($service)
	{
		return sprintf('login?service=%s', urlencode($service));
	}

	protected function getValidationUrl($ticket, $service)
	{
		return sprintf('serviceValidate?ticket=%s&service=%s', urlencode($ticket), urlencode($service));
	}

	public function getAbsoluteLoginUrl($service)
	{
		return sprintf('%s%s', $this->getConfig('base_uri'), $this->getLoginUrl($service));
	}

	public function getAbsoluteValidationUrl($ticket, $service)
	{
		return sprintf('%s%s', $this->getConfig('base_uri'), $this->getValidationUrl($ticket, $service));
	}
}
