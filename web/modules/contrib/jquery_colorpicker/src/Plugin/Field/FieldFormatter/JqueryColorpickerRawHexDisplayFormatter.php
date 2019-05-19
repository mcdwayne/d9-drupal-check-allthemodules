<?php

namespace Drupal\jquery_colorpicker\Plugin\Field\FieldFormatter;

/**
 * Raw hexadecimal formatter for Color API Color fields.
 *
 * @FieldFormatter(
 *   id = "jquery_colorpicker_raw_hex_display",
 *   label = @Translation("Raw Hexadecimal Color"),
 *   description = @Translation("Displays the color in hexadecimal color format, with no wrappers"),
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
class JqueryColorpickerRawHexDisplayFormatter extends JqueryColorpickerDisplayFormatterBase {}
