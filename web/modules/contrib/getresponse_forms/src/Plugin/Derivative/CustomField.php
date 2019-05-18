<?php

namespace Drupal\getresponse_forms\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides field definitions for GetResponse custom fields.
 *
 * @see \Drupal\getresponse_forms\Plugin\GetresponseFormsField\CustomField
 */
class CustomField extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $custom_fields = getresponse_get_custom_fields();
    foreach ($custom_fields as $key => $field) {
      $this->derivatives[$key] = $base_plugin_definition;
      $this->derivatives[$key]['admin_label'] = t('GetResponse Custom Field: @name', array('@name' => $field->name));
      $this->derivatives[$key]['label'] = t('Custom: @name', array('@name' => $field->name));
      $this->derivatives[$key]['plugin_id'] = 'getresponse_forms_custom_field:' . $field->customFieldId;
      $this->derivatives[$key]['name'] = $field->name;
      $this->derivatives[$key]['customFieldId'] = $field->customFieldId;
    }

    return $this->derivatives;
  }

}
