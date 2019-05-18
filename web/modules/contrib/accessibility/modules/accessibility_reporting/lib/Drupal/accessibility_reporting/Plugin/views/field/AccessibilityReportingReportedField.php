<?php

/**
 * @file
 * Definition of Drupal\accessibility_reporting\Plugin\views\field\AccessibilityReportingReportedField.
 */

namespace Drupal\accessibility_reporting\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Component\Annotation\PluginID;
use Drupal\field\FieldInfo;
use Drupal\field\Field;

/**
 * Field handler to present field name of a reported accessibility problem
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("accessibility_reporting_field")
 */
class AccessibilityReportingReportedField extends FieldPluginBase {

  public function query() {
    $this->additional_fields['accessibility_reporting_entity_type'] = array('table' => 'accessibility_reporting',
      'field' => 'entity_type');
    $this->additional_fields['accessibility_reporting_bundle'] = array('table' => 'accessibility_reporting',
      'field' => 'bundle');
    parent::query();
    
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $field_name = $this->getValue($values);
    $entity_type = $this->getValue($values, 'accessibility_reporting_entity_type');
    $bundle = $this->getValue($values, 'accessibility_reporting_bundle');
    $field = Field::fieldInfo()->getInstance($entity_type, $bundle, $field_name);
		return $field['label'];
  }

}

