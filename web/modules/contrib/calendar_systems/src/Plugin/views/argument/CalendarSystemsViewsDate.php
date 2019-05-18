<?php

namespace Drupal\calendar_systems\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Date;

/**
 * Adds localization support.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("date")
 */
class CalendarSystemsViewsDate extends Date {

  use CalendarSystemsArgHandlerTrait;

}
