<?php

/**
 * @file
 * Definition of Drupal\tmgmt_smartling\Plugin\views\field\TmgmtSmartlingDashboardLink
 */

namespace Drupal\tmgmt_smartling\Plugin\views\field;

use Drupal\tmgmt_smartling\Plugin\tmgmt\Translator\SmartlingTranslator;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("tmgmt_smartling_dashboard_link")
 */
class TmgmtSmartlingDashboardLink extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $job = $values->_entity;
    $translator = ($job->hasTranslator()) ? $job->getTranslatorPlugin() : null;

    if (empty($translator) || !($translator instanceof SmartlingTranslator)) {
      return '';
    }
    $proj_id = $job->getTranslator()->getSetting('project_id');
    $file_name = $translator->getFileName($job);

    return [
      '#theme' => 'smartling_dashboard_link',
      '#proj_id' => $proj_id,
      '#file_name' => $file_name
    ];
  }
}
