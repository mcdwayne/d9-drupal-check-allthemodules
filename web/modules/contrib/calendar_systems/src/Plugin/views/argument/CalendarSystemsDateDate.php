<?php

namespace Drupal\calendar_systems\Plugin\views\argument;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\views\argument\Date;
use Drupal\views\FieldAPIHandlerTrait;

/**
 * Abstract argument handler for dates, with localization support.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("datetime")
 */
class CalendarSystemsDateDate extends Date {

  use CalendarSystemsArgHandlerTrait;

}
