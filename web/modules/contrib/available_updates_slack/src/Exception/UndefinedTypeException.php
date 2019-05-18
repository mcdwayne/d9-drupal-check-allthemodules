<?php

namespace Drupal\available_updates_slack\Exception;

class UndefinedTypeException extends \Exception {
    public function __construct(string $message, int $code = 0, \Throwable $ex = null) {
        return parent::__construct($message, $code, $ex);
    }
}