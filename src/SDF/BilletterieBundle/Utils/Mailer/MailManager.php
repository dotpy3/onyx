<?php

namespace SDF\BilletterieBundle\Utils\Mailer;

use \Swift_Message;

class MailManager
{
	protected $mailer;
	protected $templating;
	protected $from;
	protected $replyTo;
	protected $sendMailInTextFormatOnly;
	protected $checkoutSubject;
	protected $informationsSubject;

	public function __construct($mailer, $templating, $from, $replyTo, $sendMailInTextFormatOnly = false, $checkoutSubject = '', $informationsSubject = '')
	{
		$this->mailer = $mailer;
		$this->templating = $templating;
		$this->from = $from;
		$this->replyTo = $replyTo;
		$this->sendMailInTextFormatOnly = $sendMailInTextFormatOnly;
		$this->checkoutSubject = $checkoutSubject;
		$this->informationsSubject = $informationsSubject;

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
			$message->setBody($this->templating->render('SDFBilletterieBundle:Mail/Ticket:confirmation.txt.twig', array(
				'ticket' => $ticket
			)), 'text/plain');
		} else {
			$message->setBody($this->templating->render('SDFBilletterieBundle:Mail/Ticket:confirmation.html.twig', array(
				'ticket' => $ticket
			)), 'text/html');
		}


		return $this->mailer->send($message);
	}

	public function sendInformationsMail($user)
	{
		$message = Swift_Message::newInstance()
			->setSubject($this->informationsSubject)
			->setFrom($this->from)
			->setReplyTo($this->replyTo)
			->setTo($user->getEmail())
		;

		if ($this->sendMailInTextFormatOnly) {
			$message->setBody($this->templating->render('SDFBilletterieBundle:Mail/User:informations.txt.twig', array(
				'user' => $user
			)), 'text/plain');
		} else {
			$message->setBody($this->templating->render('SDFBilletterieBundle:Mail/User:informations.html.twig', array(
				'user' => $user
			)), 'text/html');
		}


		return $this->mailer->send($message);
	}
}