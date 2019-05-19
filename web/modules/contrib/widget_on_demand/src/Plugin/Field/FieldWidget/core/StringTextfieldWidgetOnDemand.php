<?php

/**
 * @file
 * Contains \Drupal\widget_on_demand\Plugin\Field\FieldWidget\core\StringTextfieldWidgetOnDemand.
 */

namespace Drupal\widget_on_demand\Plugin\Field\FieldWidget\core;

use Drupal\widget_on_demand\Plugin\Field\FieldWidget\WidgetOnDemandTrait;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;

/**
 * Plugin implementation of the 'string_textfield' widget on demand.
 *
 * @FieldWidget(
 *   id = "string_textfield_on_demand",
 *   label = @Translation("Textfield - on demand"),
 *   field_types = {
 *     "string"
 *   },
 *   weight = 100,
 * )
 */
class StringTextfieldWidgetOnDemand extends StringTextfieldWidget {

  use WidgetOnDemandTrait;

}
