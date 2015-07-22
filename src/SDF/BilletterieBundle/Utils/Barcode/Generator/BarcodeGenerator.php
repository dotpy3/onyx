<?php

namespace SDF\BilletterieBundle\Utils\Barcode\Generator;

use Doctrine\ORM\EntityRepository;
use SDF\BilletterieBundle\Exception\UndefinedMethodException;

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

	public function generateUniqueBarcode()
	{
		$barcode = null;

		do {
			$barcode = $this->generateBarcode();

			$isBarcodeUnique = $this->checkBarcodeUnicity($barcode);
		} while (!$isBarcodeUnique);

		return $barcode;
	}

	private function generateBarcode()
	{
		return (integer) mt_rand(0, $this->maxNumber);
	}

	private function checkBarcodeUnicity($barcode)
	{
		if (!method_exists($this->entityRepository, 'findOneByBarcode')) {
			throw new UndefinedMethodException(sprintf('Impossible to check barcode unicity: %s method is not defined.', get_class($this->entityRepository). '::findOneByBarcode'));
		}

		$isBarcodeUnique = $this->entityRepository->findOneByBarcode($barcode);

		return is_null($isBarcodeUnique);
	}
}