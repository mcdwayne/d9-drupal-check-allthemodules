<?php

namespace Drupal\crm_core_match\Plugin\crm_core_match\field;

/**
 * Class for handling select fields.
 */
class SelectFieldHandler extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function getOperators($property = 'value') {
    return [
      'equals' => t('Equals'),
    ];
  }

}
