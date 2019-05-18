<?php

namespace Drupal\datex\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Date;

/**
 * Adds localization support.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("date")
 */
class DatexViewsDate extends Date {

  use DatexArgHandlerTrait;

}
