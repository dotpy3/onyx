<?php

namespace SDF\BilletterieBundle\Exception;

use Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UndefinedMethodException extends HttpException
{
	public function __construct($message = null, Exception $previous = null, $code = 0)
	{
		parent::__construct(500, $message, $previous, array(), $code);
	}
}