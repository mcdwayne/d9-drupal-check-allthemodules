<?php

namespace Drupal\defined_table\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Represents a configurable entity defined_table field.
 */
class DefinedTableFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   *
   * We don't use default values for this field.
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    return [];
  }

}
