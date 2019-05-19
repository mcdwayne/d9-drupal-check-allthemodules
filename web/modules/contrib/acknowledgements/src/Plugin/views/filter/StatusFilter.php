<?php

namespace Drupal\sign_for_acknowledgement\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Cache\Cache;

/**
 * Simple filter to handle greater than/less than filters
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("sfa_status")
 */
class StatusFilter extends \Drupal\views\Plugin\views\filter\FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function getValueOptions() {
    $dbman = \Drupal::service('sign_for_acknowledgement.db_manager');
	  $options = $dbman->statuses();
    $this->valueOptions = $options;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {

    if (empty($this->valueOptions)) {
      // Initialize the array of possible values for this filter.
      $this->getValueOptions();
    }

	//$exposed = $form_state->get('exposed');

	$form['value'] = [
        '#type' => 'select',
        '#title' => t('Select status'),
        '#size' => 1,
	      '#options' => $this->valueOptions,
        '#default_value' => empty($this->value[0])? '0' : $this->value[0],
      ];
	
  }

  /**
   * {@inheritdoc}
   */
  function query() {
    $this->ensureMyTable();
    $dbman = \Drupal::service('sign_for_acknowledgement.db_manager');
//  MYSQL only (non-agnostic) code has been removed
    switch ($this->value[0]) {
      case $dbman::SIGNED_OK:
        $db_and = new Condition('AND');
        $db_and->isNotNull('sfa.node_id');
        $this->query->addWhere(0, $db_and);
        break;
      case $dbman::TO_BE_SIGNED:
        $db_and = new Condition('AND');
        $db_and->isNull('sfa.node_id');
        $this->query->addWhere(0, $db_and);
        break;
    }

    $this->query->distinct = TRUE;
  }
}
