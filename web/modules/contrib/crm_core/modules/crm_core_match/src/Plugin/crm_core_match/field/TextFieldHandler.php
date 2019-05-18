<?php

namespace Drupal\crm_core_match\Plugin\crm_core_match\field;

/**
 * Class for evaluating text fields.
 *
 * @CrmCoreMatchFieldHandler (
 *   id = "text"
 * )
 */
class TextFieldHandler extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function getOperators($property = 'value') {
    return [
      '=' => t('Equals'),
      'STARTS_WITH' => t('Starts with'),
      'ENDS_WITH' => t('Ends with'),
      'CONTAINS' => t('Contains'),
    ];
  }

}
