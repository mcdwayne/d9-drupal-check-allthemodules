<?php

namespace Drupal\dhis\Exceptions;


use Throwable;

class MissingDimensionsException extends \Exception
{
    private $dimensions;

    public function __construct($message = "", $code = 0, Throwable $previous = null, $dimensions)
    {
        parent::__construct($message, $code, $previous);
        $this->dimensions = $dimensions;
    }
    public function errorMessage(){
        $message = '';
        if (empty($this->dimensions['dx'])){
            $message .= 'data element | ';
        }

        if (empty($this->dimensions['ou'])){
            $message .= ' organisation unit | ';
        }
        if (empty($this->dimensions['pe'])){
            $message .= ' period | ';
        }
        return 'Missing '.$message.' dimensions';
    }
}