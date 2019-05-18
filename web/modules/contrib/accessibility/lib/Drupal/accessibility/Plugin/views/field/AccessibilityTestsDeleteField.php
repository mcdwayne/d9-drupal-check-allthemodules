<?php

/**
 * @file
 * Definition of Drupal\accessibility_reporting\Plugin\views\field\AccessibilityTestsDeleteField.
 */

namespace Drupal\accessibility\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Component\Annotation\PluginID;
use Drupal\field\FieldInfo;
use Drupal\field\Field;

/**
 * Field handler to present accessibility test status
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("accessibility_test_delete")
 */
class AccessibilityTestsDeleteField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if(!accessibility_test_access('delete', $values->_entity)) {
      return;
    }
    return l(t('delete'), 'accessibility-test/' . $values->_entity->id() . '/delete');
  }

}

