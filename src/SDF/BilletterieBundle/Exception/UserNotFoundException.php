<?php

namespace SDF\BilletterieBundle\Exception;

use Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserNotFoundException extends NotFoundHttpException
{
	public function __construct($message = null, Exception $previous = null, $code = 0)
	{
		parent::__construct($message, $previous, $code);
	}
}