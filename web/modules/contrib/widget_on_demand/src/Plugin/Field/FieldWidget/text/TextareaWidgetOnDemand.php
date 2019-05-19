<?php

/**
 * @file
 * Contains \Drupal\widget_on_demand\Plugin\Field\FieldWidget\text\TextareaWidgetOnDemand.
 */

namespace Drupal\widget_on_demand\Plugin\Field\FieldWidget\text;

use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget;

/**
 * Plugin implementation of the 'text_textarea' widget on demand.
 *
 * @FieldWidget(
 *   id = "text_textarea_on_demand",
 *   label = @Translation("Text area (multiple rows) - on demand"),
 *   field_types = {
 *     "text_long"
 *   },
 *   weight = 100,
 * )
 */
class TextareaWidgetOnDemand extends TextareaWidget {

  use WidgetOnDemandForTextFormatTrait;

}
