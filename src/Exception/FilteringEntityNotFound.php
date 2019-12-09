<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Exception;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class FilteringEntityNotFound extends \RuntimeException
{
    public function __construct($message = "", $code = Response::HTTP_NOT_FOUND, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
