<?php

namespace Drupal\warmer\Plugin;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for warmer plugins that implement settings forms.
 *
 * @see \Drupal\warmer\Annotation\Warmer
 * @see \Drupal\warmer\Plugin\WarmerPluginManager
 * @see \Drupal\warmer\Plugin\WarmerInterface
 *
 * @see plugin_api
 */
abstract class WarmerPluginBase extends PluginBase implements ContainerFactoryPluginInterface, PluginFormInterface, ConfigurablePluginInterface, WarmerInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $settings = $container->get('config.factory')
      ->get('warmer.settings')
      ->get('warmers');
    $plugin_settings = empty($settings[$plugin_id]) ? [] : $settings[$plugin_id];
    $configuration = array_merge($plugin_settings, $configuration);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
        'id' => $this->getPluginId(),
      ] + $this->configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'frequency' => 5 * 60,
      'batchSize' => 50,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $frequency = $form_state->getValue('frequency');
    $batch_size = $form_state->getValue('batchSize');
    if (!is_numeric($frequency) || $frequency < 0) {
      $form_state->setError($form[$this->getPluginId()]['frequency'], $this->t('Frequency should be a positive number.'));
    }
    if (!is_numeric($batch_size) || $batch_size < 1) {
      $form_state->setError($form[$this->getPluginId()]['batchSize'], $this->t('Batch size should be a number greater than 1.'));
    }
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
  final public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration() + $this->defaultConfiguration();
    $plugin_id = $configuration['id'];
    $definition = $this->getPluginDefinition();
    $form[$plugin_id] = empty($form[$plugin_id]) ? [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => empty($definition['label']) ? $plugin_id : $definition['label'],
      '#group' => 'warmers',
      '#tree' => TRUE,
    ] : $form[$plugin_id];
    if (!empty($definition['description'])) {
      $form[$plugin_id]['description'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $definition['description'],
      ];
    }
    $form[$plugin_id]['frequency'] = [
      '#type' => 'number',
      '#title' => $this->t('Frequency'),
      '#description' => $this->t('Only re-enqueue warming operations after at lease this many seconds have passed.'),
      '#default_value' => $this->getFrequency(),
    ];
    $form[$plugin_id]['batchSize'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch Size'),
      '#description' => $this->t('Number of items to enqueue and process in a single go.'),
      '#default_value' => $this->getBatchSize(),
    ];

    $subform_state = SubformState::createForSubform($form[$plugin_id], $form, $form_state);
    $form[$plugin_id] = $this->addMoreConfigurationFormElements($form[$plugin_id], $subform_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues() + $this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchSize() {
    $configuration = $this->getConfiguration();
    return $configuration['batchSize'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFrequency() {
    $configuration = $this->getConfiguration();
    return $configuration['frequency'];
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    $configuration = $this->getConfiguration();
    $last_run = $this->state->get('previous_enqueue_time:' . $configuration['id']);
    return $this->time->getRequestTime() > $last_run + $this->getFrequency();
  }

  /**
   * {@inheritdoc}
   */
  public function markAsEnqueued() {
    $configuration = $this->getConfiguration();
    $this->state->set(
      'previous_enqueue_time:' . $configuration['id'],
      $this->time->getRequestTime()
    );
  }

}
