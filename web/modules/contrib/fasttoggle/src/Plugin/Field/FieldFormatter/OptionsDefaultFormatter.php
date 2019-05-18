<?php

namespace Drupal\fasttoggle\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\fasttoggle\viewElementsTrait;

/**
 * Plugin implementation of the 'list_default' formatter.
 *
 * @FieldFormatter(
 *   id = "fasttoggle_list",
 *   label = @Translation("Fasttoggle"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *   }
 * )
 */
class OptionsDefaultFormatter extends \Drupal\options\Plugin\Field\FieldFormatter\OptionsDefaultFormatter {
  use viewElementsTrait;
}
