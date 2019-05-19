<?php

namespace Drupal\colorapi\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetInterface;

/**
 * The default widget for Color fields.
 *
 * @FieldWidget(
 *   id = "colorapi_color_widget",
 *   label = @Translation("Textfield Input"),
 *   field_types = {
 *      "colorapi_color_field"
 *   }
 * )
 */
class ColorapiWidget extends ColorapiWidgetBase implements WidgetInterface {}
