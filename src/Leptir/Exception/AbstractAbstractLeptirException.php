<?php

namespace Leptir\Exception;

abstract class AbstractLeptirException extends \Exception
{
    public function __construct($code)
    {
        $message = $this->getMessageForCode($code);
        parent::__construct($message, $code);
    }

    abstract protected function getMessageForCode($code);
}
