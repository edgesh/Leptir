<?php

namespace Leptir\Exception;

class LeptirInputException extends AbstractLeptirException
{
    const NOT_AN_INT = 1;
    const NOT_A_STRING = 2;
    const NOT_A_FLOAT = 3;
    const NOT_AN_ARRAY = 4;
    const VALUE_NOT_DEFINED = 5;

    private $fieldName = 'unknown';

    public function __construct($fieldName, $code)
    {
        $this->fieldName = $fieldName;
        parent::__construct($code);
    }

    protected function getMessageForCode($code)
    {
        switch($code)
        {
            case self::NOT_AN_INT:
                return "Input value is not an integer. Parameter: " . $this->fieldName;
            case self::NOT_A_FLOAT:
                return "Input value is not a float. Parameter: " . $this->fieldName;
            case self::NOT_A_STRING:
                return "Input value is not a string. Parameter: " . $this->fieldName;
            case self::NOT_AN_ARRAY:
                return "Input value is not an array. Parameter: " . $this->fieldName;
            case self::VALUE_NOT_DEFINED:
                return "Value is not defined. Parameters: " . $this->fieldName;
            default:
                return "Unknown input exception for parameter " . $this->fieldName;
        }
    }
}
