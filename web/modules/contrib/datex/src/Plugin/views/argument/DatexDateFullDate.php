<?php

namespace Drupal\datex\Plugin\views\argument;

use Drupal\datetime\Plugin\views\argument\FullDate;

/**
 * Argument handler for a full date (CCYYMMDD).
 *
 * @ViewsArgument("datetime_full_date")
 */
class DatexDateFullDate extends FullDate {

  use DatexArgHandlerTrait;

}
