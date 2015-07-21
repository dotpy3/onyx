<?php

namespace SDF\BilletterieBundle\Utils\Mailer;

use \Swift_Message;

class MailManager
{
	protected $mailer;
	protected $from;
	protected $replyTo;
	protected $sendMailInTextFormatOnly;
	protected $checkoutSubject;

	public function __construct($mailer, $from, $replyTo, $sendMailInTextFormatOnly = false, $checkoutSubject = '')
	{
		$this->mailer = $mailer;
		$this->from = $from;
		$this->replyTo = $replyTo;
		$this->sendMailInTextFormatOnly = $sendMailInTextFormatOnly;
		$this->checkoutSubject = $checkoutSubject;

		return $this;
	}

	public function sendConfirmationMail($ticket)
	{
		$message = Swift_Message::newInstance()
			->setSubject($this->checkoutSubject)
			->setFrom($this->from)
			->setReplyTo($this->replyTo)
			->setTo($ticket->getUser()->getEmail())
		;

		if ($this->sendMailInTextFormatOnly) {
			$message->setBody($this->renderView('SDFBilletterieBundle:Mail/Ticket:confirmation.txt.twig', array(
				'ticket' => $ticket
			)),'text/plain');
		} else {
			$message->setBody($this->renderView('SDFBilletterieBundle:Mail/Ticket:confirmation.html.twig', array(
				'ticket' => $ticket
			)),'text/html');
		}


		return $this->mailer->send($message);
	}
}