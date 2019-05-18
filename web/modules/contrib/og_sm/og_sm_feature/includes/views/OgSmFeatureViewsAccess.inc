<?php

/**
 * @file
 * Views Access plugin based on the status of a feature within a Site.
 */

/**
 * Access plugin that provides feature based access control.
 *
 * @ingroup views_access_plugins
 */
class OgSmFeatureViewsAccess extends views_plugin_access {

  /**
   * {@inheritdoc}
   */
  public function access($account) {
    return og_sm_feature_access($this->options['feature']);
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  public function get_access_callback() {
    // @codingStandardsIgnoreEnd
    return array('og_sm_feature_access', array($this->options['feature']));
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  function summary_title() {
    // @codingStandardsIgnoreEnd
    $features = og_sm_feature_info();
    $feature_name = $this->options['feature'];

    if (isset($features[$feature_name]['name'])) {
      return $features[$feature_name]['name'];
    }

    return $feature_name;
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  function option_definition() {
    // @codingStandardsIgnoreEnd
    $options = parent::option_definition();
    $options['feature'] = array('default' => NULL);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  function options_form(&$form, &$form_state) {
    // @codingStandardsIgnoreEnd
    parent::options_form($form, $form_state);

    // Feature options.
    $options = array();
    $features = og_sm_feature_info();
    foreach ($features as $feature => $info) {
      $options[$feature] = $info['name'];
    }

    // Features options settings.
    $form['feature'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('Feature'),
      '#default_value' => $this->options['feature'],
      '#description' => t(
        'Only sites which have the selected feature enabled will be able to access this display.'
      ),
    );
  }

}
