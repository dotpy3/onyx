<?php

namespace SDF\BilletterieBundle\Utils\Pdf\Generator;

use SplFileInfo;

use SDF\BilletterieBundle\Utils\Pdf\Pdf;
use SDF\BilletterieBundle\Entity\Billet;
use SDF\BilletterieBundle\Exception\NullTicketException;
use SDF\BilletterieBundle\Exception\ImageNotFoundException;

class PdfGenerator
{
	protected $imagePath;

	/**
	 * Service constructor
	 *
	 * @param string $imagePath The ticket background-image path
	 * @return PdfGenerator
	 */
	public function __construct($imagePath)
	{
		$this->imagePath = $imagePath;

		return $this;
	}

	/**
	 * Generate a PDF with Billet informations
	 *
	 * @param Billet $ticket The Billet entity with informations to print
	 * @throws NullTicketException Occurs if the provided Billet is null
	 * @throws ImageNotFoundException Occurs if the provided imagePath does not exists or cannot be opened
	 * @return string
	 */
	public function generateTicket(Billet $ticket)
	{
		if (is_null($ticket)) {
			throw new NullTicketException('The ticket you want to generate a PDF from does not exists.');
		}

		$imageInfo = new SplFileInfo($this->imagePath);

		if (!($imageInfo->isFile()) || !($imageInfo->isReadable())) {
			throw new ImageNotFoundException('The tickets PDF background-image cannot be opened.');
		}

		$pdf = new Pdf();

		$pdf->Open();
		$pdf->AddPage('L');
		$pdf->SetAutoPageBreak(true, '5');
		$pdf->Image($this->imagePath, 0, 0, 297, 210);
		$pdf->SetFont('arial', 'B', '20');
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetXY(174, 13+11);
		$pdf->Write(10, iconv("UTF-8", "ISO-8859-1", ucfirst(strtolower($ticket->getPrenom()))));
		$pdf->SetXY(174, 21+11);
		$pdf->Write(10, iconv("UTF-8", "ISO-8859-1", strtoupper($ticket->getNom())));
		$pdf->SetFont('arial', '', '20');
		$pdf->SetXY(174, 42+11);
		$pdf->Write(10, iconv("UTF-8", "ISO-8859-1", strtoupper($ticket->getTarif()->getNomTarif())));

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('arial', '', '11');
		$pdf->SetXY(174, 6+11);
		$pdf->Write(10, "Num".chr(233)."ro de billet : ". $ticket->getId());

		$pdf->SetXY(174, 32+11);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->Write(10, "Prix TTC : " . $ticket->getTarif()->getPrix() . ' '.chr(128));

		$pdf->SetXY(174, 58+11);
		$pdf->Write(10, "Billet achet".chr(233)." par : ".iconv("UTF-8",  "ISO-8859-1", ucfirst($ticket->getUser()->getFirstname()) . ' ' . strtoupper($ticket->getUser()->getName())));
		$pdf->SetTextColor(0, 0, 0);
		$pdf->EAN13(174, 72+11, $ticket->getBarcode(),  12,  1);

		return $pdf->Output('', 'I');
	}
}