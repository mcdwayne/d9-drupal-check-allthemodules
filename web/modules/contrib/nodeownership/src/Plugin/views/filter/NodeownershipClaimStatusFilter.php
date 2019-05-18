<?php

namespace Drupal\nodeownership\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter by claim status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("nodeownership_claim_status_filter")
 */
class NodeownershipClaimStatusFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    $form['value'] = array(
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => array(
        0 => $this->t('Pending'),
        1 => $this->t('Approved'),
        2 => $this->t('Declined'),
      ),
      '#default_value' => $this->value,
      '#required' => FALSE,
    );
  }

}
