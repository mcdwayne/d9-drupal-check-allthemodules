<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna;

/**
 * Shared date related functionality.
 */
trait DateTrait {

  /**
   * Formats the given datetime.
   *
   * PHP's default ISO8601 implementation contains timezone and
   * Klarna doesn't support other timezones than UTC+0.
   *
   * @param \DateTime $dateTime
   *   The datetime to format.
   *
   * @return string
   *   The formatted date.
   */
  public function format(\DateTime $dateTime) : string {
    return $dateTime->format('Y-m-d\TH:i:s') . 'Z';
  }

}
