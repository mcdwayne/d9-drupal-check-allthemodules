<?php

namespace Drupal\drupal_inquicker\Formatter;

use Drupal\drupal_inquicker\traits\CommonUtilities;
use Drupal\drupal_inquicker\traits\DependencyInjection;

/**
 * Base class for formatting objects for specific output.
 */
abstract class Formatter {

  use CommonUtilities;
  use DependencyInjection;

  /**
   * React to an exception while formatting.
   *
   * By default a throwable is thrown in case of an error; subclasses can
   * override this method if they want to return a sensible default and log
   * the throwable, for example.
   *
   * @param \Throwable $t
   *   A throwable.
   *
   * @throws \Throwable
   */
  public function catchError(\Throwable $t) {
    throw $t;
  }

  /**
   * Format arbitrary data.
   *
   * @param mixed $data
   *   Data. If the data is of the expected type, it will be formatted; if
   *   not an error will occur.
   *
   * @return mixed
   *   Formatted.
   *
   * @throws \Throwable
   */
  public function format($data) {
    try {
      $this->validateSource($data);
      return $this->formatValidatedSource($data);
    }
    catch (\Throwable $t) {
      return $this->catchError($t);
    }
  }

  /**
   * Format validated data.
   *
   * @param mixed $data
   *   Data which has already passed through validateSource().
   *
   * @return mixed
   *   The data, formatted.
   *
   * @throws \Throwable
   */
  abstract public function formatValidatedSource($data);

  /**
   * Throw an exception if the data is not valid.
   *
   * @param mixed $data
   *   Data which has already passed through validateSource().
   *
   * @throws \Throwable
   */
  abstract public function validateSource($data);

}
