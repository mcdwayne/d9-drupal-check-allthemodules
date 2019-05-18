<?php
namespace Drupal\dhis\Exceptions;

use Throwable;

class DhisEntityExistsException extends \Exception
{
    public function errorMessage(){
        return $this->getMessage();
    }
}