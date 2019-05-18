<?php

namespace Drupal\calendar_systems\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\FullDate;

/**
 * Argument handler for a full date (CCYYMMDD)
 *
 * @ViewsArgument("date_fulldate")
 */
class CalendarSystemsViewsFullDate extends FullDate {

  use CalendarSystemsArgHandlerTrait;

}
