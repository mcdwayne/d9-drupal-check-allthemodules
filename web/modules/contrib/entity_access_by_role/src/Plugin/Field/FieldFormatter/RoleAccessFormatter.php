<?php

namespace Drupal\entity_access_by_role\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'role_access_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "role_access_widget",
 *   module = "entity_access_by_role",
 *   label = @Translation("Empty formatter"),
 *   field_types = {
 *     "role_access"
 *   }
 * )
 */
class RoleAccessFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Does not actually output anything.
    return [];
  }

}
