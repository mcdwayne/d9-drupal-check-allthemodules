<?php

/**
 * @file
 * Fasttoggle field formatter.
 */

namespace Drupal\fasttoggle\Plugin\Field\FieldFormatter;

use Drupal\fasttoggle\viewElementsTrait;

/**
 * Plugin implementation of the 'Fasttoggle' field formatter.
 * @FieldFormatter(
 *   id = "fasttoggle_boolean",
 *   label = @Translation("Fasttoggle"),
 *   field_types = {
 *    "boolean",
 *   }
 * )
 */
class BooleanFormatter extends \Drupal\Core\Field\Plugin\Field\FieldFormatter\BooleanFormatter {
  use viewElementsTrait;
}