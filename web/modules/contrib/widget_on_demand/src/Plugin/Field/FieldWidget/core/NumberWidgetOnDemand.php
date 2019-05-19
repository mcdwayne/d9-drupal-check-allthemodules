<?php

/**
 * @file
 * Contains \Drupal\widget_on_demand\Plugin\Field\FieldWidget\core\NumberWidgetOnDemand.
 */

namespace Drupal\widget_on_demand\Plugin\Field\FieldWidget\core;

use Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget;
use Drupal\widget_on_demand\Plugin\Field\FieldWidget\WidgetOnDemandTrait;

/**
 * Plugin implementation of the 'number' widget on demand.
 *
 * @FieldWidget(
 *   id = "number_on_demand",
 *   label = @Translation("Number field - on demand"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float"
 *   },
 *   weight = 100,
 * )
 */
class NumberWidgetOnDemand extends NumberWidget {

  use WidgetOnDemandTrait;

}
