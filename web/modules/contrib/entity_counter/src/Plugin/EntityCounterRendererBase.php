<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\entity_counter\Entity\EntityCounterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for an entity counter renderer.
 *
 * @see \Drupal\entity_counter\Plugin\EntityCounterRendererInterface
 * @see \Drupal\entity_counter\Plugin\EntityCounterRendererManagerInterface
 * @see plugin_api
 */
abstract class EntityCounterRendererBase extends PluginBase implements EntityCounterRendererInterface {

  /**
   * The entity counter.
   *
   * @var \Drupal\entity_counter\Entity\EntityCounterInterface
   */
  protected $entityCounter = NULL;

  /**
   * The entity counter renderer label.
   *
   * @var string
   */
  protected $label;

  /**
   * Constructs an EntityCounterRendererBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['ratio' => 1.00];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'label' => $this->getLabel(),
      'settings' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'label' => $this->label(),
      'settings' => [],
    ];

    $this->label = $configuration['label'];
    $this->configuration = $configuration['settings'] + $this->defaultConfiguration();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form, $form_state);

    $form['ratio'] = [
      '#type' => 'number',
      '#step' => '0.01',
      '#title' => $this->t('Ratio'),
      '#description' => $this->t('Use this setting to show a value based on a conversion ratio.'),
      '#default_value' => $this->getConfiguration()['settings']['ratio'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validate operations.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCounter() {
    return $this->entityCounter;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityCounter(EntityCounterInterface $entity_counter) {
    $this->entityCounter = $entity_counter;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label ?: $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function render(array &$element) {
    // Renders the form element.
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = [];
    foreach ($this->configuration as $key => $value) {
      $summary[] = $this->t('@setting: @value.', [
        '@setting' => Unicode::lcfirst($key),
        '@value' => $value,
      ]);
    }

    return $summary;
  }

  /**
   * Apply submitted form state to configuration.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function applyFormStateToConfiguration(array &$form, FormStateInterface $form_state) {
    // This method receives a sub form state instead of the full form state.
    // @See https://www.drupal.org/node/2798261
    if ($form_state instanceof SubformStateInterface) {
      $values = NestedArray::getValue($form_state->getCompleteFormState()->getValues(), $form['#parents']);
    }
    else {
      $values = $form_state->getValues();
    }
    $values = empty($values) ? $this->getConfiguration() : $values;

    foreach ($values as $key => $value) {
      if (array_key_exists($key, $this->configuration)) {
        $this->configuration[$key] = $value;
      }
    }
  }

}
