<?php

/**
 * @file
 * Contains \Drupal\widget_on_demand\Plugin\Field\FieldWidget\core\StringTextareaWidgetOnDemand.
 */

namespace Drupal\widget_on_demand\Plugin\Field\FieldWidget\core;

use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;
use Drupal\widget_on_demand\Plugin\Field\FieldWidget\WidgetOnDemandTrait;

/**
 * Plugin implementation of the 'string_textarea' widget on demand.
 *
 * @FieldWidget(
 *   id = "string_textarea_on_demand",
 *   label = @Translation("Text area (multiple rows) - on demand"),
 *   field_types = {
 *     "string_long"
 *   },
 *   weight = 100,
 * )
 */
class StringTextareaWidgetOnDemand extends StringTextareaWidget {

  use WidgetOnDemandTrait;

}
