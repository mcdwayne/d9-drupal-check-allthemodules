<?php

namespace Drupal\crm_core_match\Plugin\crm_core_match\field;

/**
 * Class for evaluating phone_number fields.
 */
class PhoneNumberFieldHandler extends FieldHandlerBase {

  /**
   * Render the field.
   *
   * @param array $field
   *   Field.
   * @param array $field_info
   *   Field info.
   * @param mixed $form
   *   Form.
   *
   * @see DefaultMatchingEngineFieldType::fieldRender()
   */
  public function fieldRender(array $field, array $field_info, &$form) {
    foreach ($field_info['columns'] as $item => $info) {
      $description = '';
      switch ($item) {
        case 'number':
          $description = t('Number');
          break;

        case 'country_codes':
          $description = t('Country code');
          break;

        case 'extension':
          $description = t('Extension');
          break;
      }
      $field_item['field_name'] = $field['field_name'];
      $field_item['label'] = $field['label'] . ': ' . $description;
      $field_item['bundle'] = $field['bundle'];
      $field_item['field_item'] = $item;

      // TODO: Missing parameter.
      $item = new SelectFieldHandler();
      $item->fieldRender($field_item, $field_info, $form);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOperators($property = 'value') {
    return [
      'equals' => t('Equals'),
    ];
  }

}
