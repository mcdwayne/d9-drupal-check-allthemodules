<?php

namespace Drupal\healthcheck_historical\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides a field handler for status fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("healthcheck_finding_status")
 */
class FindingStatusViewsField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $statuses = FindingStatus::getLabelsByConstants();
    foreach ($statuses as $status_text => $label) {
      $options[$status_text] = [
        'default' => $label,
      ];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $statuses = FindingStatus::getLabelsByConstants();

    foreach ($statuses as $status_text => $label) {
      $form[$status_text] = [
        '#title' => $label,
        '#type' => 'textfield',
        '#default_value' => $this->options[$status_text],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $statuses = FindingStatus::getTextConstants();

    $value = (int) $this->getValue($values);

    if (isset($statuses[$value])) {
      $key = $statuses[$value];
      return $this->options[$key];
    }

    return $value;
  }

}
