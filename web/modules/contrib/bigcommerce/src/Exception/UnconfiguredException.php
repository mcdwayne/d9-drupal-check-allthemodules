<?php

namespace Drupal\bigcommerce\Exception;

use Drupal\migrate\Exception\RequirementsException;

/**
 * Exception thrown when required configuration for BigCommerce does not exist.
 */
class UnconfiguredException extends RequirementsException {
}
