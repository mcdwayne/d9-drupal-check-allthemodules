<?php

namespace Drupal\commerce_funds\Exception;

/**
 * Thrown when trying to operate on a currency which exist in user balances.
 */
class CurrencyInUseException extends \Exception {}
