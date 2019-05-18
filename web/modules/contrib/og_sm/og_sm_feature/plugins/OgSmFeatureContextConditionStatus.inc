<?php

/**
 * @file
 * Context condition plugin.
 */

/**
 * Expose Site feature status as Context condition.
 */
class OgSmFeatureContextConditionStatus extends context_condition {

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  public function condition_values() {
    // @codingStandardsIgnoreEnd

    $values = array();

    $features = og_sm_feature_info();
    foreach ($features as $feature => $info) {
      $values[$feature] = $info['name'];
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  function condition_met($context, $value = NULL) {
    // @codingStandardsIgnoreEnd

    // Check first if we need to skip node forms.
    $options = $this->fetch_from_context($context, 'options');
    if ($this->isNodeForm() && empty($options['node_form'])) {
      return;
    }

    parent::condition_met($context, $value);
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  function options_form($context) {
    // @codingStandardsIgnoreEnd

    $defaults = $this->fetch_from_context($context, 'options');
    return array(
      'node_form' => array(
        '#title' => t('Set on node form'),
        '#type' => 'checkbox',
        '#description' => t('Set this context on node forms'),
        '#default_value' => isset($defaults['node_form']) ? $defaults['node_form'] : TRUE,
      ),
    );
  }

  /**
   * Loop over all features and check contexts that may have been set for it.
   *
   * @param object $site
   *   The Site object to check the feature statuses for.
   */
  public function execute($site) {
    $features = og_sm_feature_info();
    foreach ($features as $feature => $info) {
      // Do not check if feature is not enabled!
      if (!og_sm_feature_site_is_enabled($site, $feature)) {
        continue;
      }

      $this->executeContexts($feature);
    }
  }

  /**
   * Loop over all feature status contexts and check if condition is met.
   *
   * @param string $feature
   *   The feature to check.
   */
  protected function executeContexts($feature) {
    $this->values[$feature] = array();

    $contexts = $this->get_contexts($feature);
    foreach ($contexts as $context) {
      $this->condition_met($context, $feature);
    }
  }

  /**
   * Check if we are on a node form.
   *
   * @return bool
   *   Node form detected.
   */
  protected function isNodeForm() {
    if ((arg(0) !== 'node')) {
      return FALSE;
    }

    if (is_numeric(arg(1)) && arg(2) === 'edit') {
      return TRUE;
    }

    if (arg(1) === 'add') {
      return TRUE;
    }

    return FALSE;
  }

}
