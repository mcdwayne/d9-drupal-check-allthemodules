<?php

namespace Drupal\feature_toggle\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feature_toggle\FeatureInterface;

/**
 * Provides a 'Feature Toggle' condition.
 *
 * @Condition(
 *   id = "feature_toggle",
 *   label = @Translation("Feature Toggle"),
 * )
 */
class FeatureToggle extends ConditionPluginBase {

  /**
   * The feature list array.
   *
   * @var array
   */
  protected $features;

  /**
   * The list of enabled features.
   *
   * @var array
   */
  protected $enabledFeatures;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $features = \Drupal::service('feature_toggle.feature_manager')->getFeatures();
    /** @var \Drupal\feature_toggle\FeatureStatusInterface $status */
    $status = \Drupal::service('feature_toggle.feature_status');

    $features_temp = $enabled_temp = [];
    array_walk($features, function (FeatureInterface $value) use (&$features_temp, &$enabled_temp, $status) {
      $features_temp[$value->name()] = $value->label();
      if ($status->getStatus($value->name())) {
        $enabled_temp[] = $value->name();
      }
    });

    $this->features = $features_temp;
    $this->enabledFeatures = $enabled_temp;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['features'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('When the following features are enabled'),
      '#default_value' => $this->configuration['features'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', $this->features),
      '#description' => $this->t('If you select no features, the condition will evaluate to TRUE.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'features' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['features'] = array_filter($form_state->getValue('features'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Use the feature labels. They will be sanitized below.
    $features = array_intersect_key($this->features, $this->configuration['features']);
    if (count($features) > 1) {
      $features = implode(', ', $features);
    }
    else {
      $features = reset($features);
    }
    if (!empty($this->configuration['negate'])) {
      return $this->t('One of these features @features are not enabled', ['@features' => $features]);
    }
    else {
      return $this->t('One of these features @features are enabled', ['@features' => $features]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['features']) && !$this->isNegated()) {
      return TRUE;
    }
    return (bool) array_intersect($this->configuration['features'], $this->enabledFeatures);
  }

}
