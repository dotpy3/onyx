<?php

namespace SDF\BilletterieBundle\Utils\Barcode\Generator;

use Doctrine\ORM\EntityRepository;
use SDF\BilletterieBundle\Exception\UndefinedMethodException;

/**
 * Barcode Generator
 * Generates barcode
 *
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
class BarcodeGenerator
{
	protected $entityRepository;
	protected $maxNumber;

	/**
	 * Service constructor
	 *
	 * @param EntityManager $entityRepository The doctrine entity manager
	 * @param integer $maxNumber The maximal barcode number
	 * @return BarcodeGenerator
	 */
	public function __construct(EntityRepository $entityRepository, $maxNumber)
	{
		$this->entityRepository = $entityRepository;
		$this->maxNumber = $maxNumber;

		return $this;
	}

	/**
	 * Generate a unique barcode
	 *
	 * TODO
	 * If the configurated maxNumber is too low
	 * And all available barcodes have already been generated
	 * This loop will never ends...
	 *
	 * @return integer
	 */
	public function generateUniqueBarcode()
	{
		$barcode = null;

		do {
			$barcode = $this->generateRandomNumber();

			$isBarcodeUnique = $this->checkBarcodeUnicity($barcode);
		} while (!$isBarcodeUnique);

		return $barcode;
	}

	/**
	 * Generate a random integer between 0 and the configurated max value.
	 *
	 * @return integer
	 */
	private function generateRandomNumber()
	{
		return (integer) mt_rand(0, $this->maxNumber);
	}

	/**
	 * Check barcode unicity
	 * Check if a given barcode is already registred in the database
	 *
	 * @return boolean
	 */
	private function checkBarcodeUnicity($barcode)
	{
		if (!method_exists($this->entityRepository, 'findOneByBarcode')) {
			throw new UndefinedMethodException(sprintf('Impossible to check barcode unicity: %s method is not defined.', get_class($this->entityRepository). '::findOneByBarcode'));
		}

		$isBarcodeUnique = $this->entityRepository->findOneByBarcode($barcode);

		return is_null($isBarcodeUnique);
	}
}