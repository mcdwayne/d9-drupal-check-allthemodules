<?php

namespace Drupal\search_api_date_popup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\filter\SearchApiDate;

/**
 * The date popup views filter plugin.
 */
class SearchApiDatePopup extends SearchApiDate {

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $this->applyDatePopupToForm($form);
  }

  /**
   * Apply the HTML5 date popup to the views filter form.
   *
   * @param array $form
   *   The form to apply it to.
   */
  protected function applyDatePopupToForm(array &$form) {
    if (!empty($this->options['expose']['identifier'])) {
      // Detect filters that are using min/max.
      if (isset($form[$this->options['expose']['identifier']]['min'])) {
        $form[$this->options['expose']['identifier']]['min']['#type'] = 'date';
        $form[$this->options['expose']['identifier']]['max']['#type'] = 'date';
      }
      else {
        $form[$this->options['expose']['identifier']]['#type'] = 'date';
      }
    }
  }
}
