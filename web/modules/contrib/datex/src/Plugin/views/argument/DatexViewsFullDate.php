<?php

namespace Drupal\datex\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\FullDate;

/**
 * Argument handler for a full date (CCYYMMDD)
 *
 * @ViewsArgument("date_fulldate")
 */
class DatexViewsFullDate extends FullDate {

  use DatexArgHandlerTrait;

}
