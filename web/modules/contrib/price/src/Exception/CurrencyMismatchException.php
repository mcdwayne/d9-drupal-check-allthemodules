<?php

namespace Drupal\price\Exception;

/**
 * Thrown when trying to operate on monetary values with different currencies.
 */
class CurrencyMismatchException extends \InvalidArgumentException {}
