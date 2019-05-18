<?php

/**
 * @file
 * Definition of Drupal\accessibility_reporting\Plugin\views\field\AccessibilityTestsStatusField.
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
 * @PluginID("accessibility_test_status")
 */
class AccessibilityTestsStatusField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return ($values->status) ? t('Active') : t('Inactive');
  }

}
