<?php

namespace Drupal\jquery_colorpicker\Plugin\Field\FieldFormatter;

/**
 * Hexadecimal string text display formatter for Color Api Color fields.
 *
 * @FieldFormatter(
 *   id = "jquery_colorpicker_text_display",
 *   label = @Translation("Text Color"),
 *   description = @Translation("Displays the color as a hexadecimal string"),
 *   field_types = {
 *      "jquery_colorpicker"
 *   }
 * )
 *
 * @deprecated as of Jquery Colorpicker update 8200. Will be removed in Jquery
 *   Colorpicker 8.x-3.x, and/or 9.x-1.x. Running
 *   jquery_colorpicker_update_8200() requires the existence of this formatter,
 *   however the field type is obsolete after that update has been run.
 */
class JqueryColorpickerTextDisplayFormatter extends JqueryColorpickerDisplayFormatterBase {}
