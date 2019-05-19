<?php

/**
 * @file
 * Contains \Drupal\widget_on_demand\Plugin\Field\FieldWidget\text\TextfieldWidgetOnDemand.
 */

namespace Drupal\widget_on_demand\Plugin\Field\FieldWidget\text;

use Drupal\text\Plugin\Field\FieldWidget\TextfieldWidget;

/**
 * Plugin implementation of the 'text_textfield' widget on demand.
 *
 * @FieldWidget(
 *   id = "text_textfield_on_demand",
 *   label = @Translation("Text field - on demand"),
 *   field_types = {
 *     "text"
 *   },
 *   weight = 100,
 * )
 */
class TextfieldWidgetOnDemand extends TextfieldWidget {

  use WidgetOnDemandForTextFormatTrait;

}
