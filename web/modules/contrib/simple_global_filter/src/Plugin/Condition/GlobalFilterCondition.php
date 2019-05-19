<?php

namespace Drupal\simple_global_filter\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Provides a 'Global filter condition' condition to enable a condition based in module selected status.
*
* @Condition(
*   id = "global_filter_condition",
*   label = @Translation("Global filter condition"),
*   deriver = "Drupal\simple_global_filter\Plugin\Derivative\GlobalFilterCondition"
* )
*
*/
class GlobalFilterCondition extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Creates a new GlobalFilterCondition object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
   
    $global_filter = \Drupal::entityTypeManager()->getStorage('global_filter')->load($this->getDerivativeId());

    $options_tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($global_filter->getVocabulary(), 0, NULL, TRUE);

    $options = [];
    foreach($options_tree as $term) {
      $options[$term->id()] = $term->label();
    }

    $form['global_filter_' . $this->getDerivativeId()] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Global filter'),
      '#options' => $options,
      '#default_value' => $this->configuration['global_filter_' . $this->getDerivativeId()],
      '#description' => $this->t('Select the global filters.'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['global_filter_' . $this->getDerivativeId()] =
      array_filter($form_state->getValue('global_filter_' . $this->getDerivativeId()));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['global_filter_' . $this->getDerivativeId() => []] + parent::defaultConfiguration();
  }

  /**
   * Evaluates the condition and returns TRUE or FALSE accordingly.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate() {
    
    if (empty($this->configuration['global_filter_' . $this->getDerivativeId()]) && !$this->isNegated()) {
      return TRUE;
    }
    $filter = \Drupal::service('simple_global_filter.global_filter')->get($this->getDerivativeId());

    return !empty($this->configuration['global_filter_' . $this->getDerivativeId()][$filter]);
  }

  /**
   * Provides a human readable summary of the condition's configuration.
   */
  public function summary()
  {
    if (count($this->configuration['global_filter_' . $this->getDerivativeId()]) > 1) {
      $filters = $this->configuration['global_filter_' . $this->getDerivativeId()];
      $last = array_pop($filters);
      $filters = implode(', ', $filters);
      return $this->t('The global filter is @filters or @last', ['@filters' => $filters, '@last' => $last]);
    }
    $filter = reset($this->configuration['global_filter_' . $this->getDerivativeId()]);
    return $this->t('The global filter is @filter', ['@filter' => $filter]);
  }

}
