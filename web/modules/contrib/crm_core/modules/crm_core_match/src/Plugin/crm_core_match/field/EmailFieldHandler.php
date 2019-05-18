<?php

namespace Drupal\crm_core_match\Plugin\crm_core_match\field;

/**
 * Class for evaluating email fields.
 *
 * @CrmCoreMatchFieldHandler (
 *   id = "email"
 * )
 */
class EmailFieldHandler extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function getOperators($property = 'value') {
    return [
      '=' => t('Equals'),
    ];
  }

}
