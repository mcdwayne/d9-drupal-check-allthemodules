<?php

namespace Drupal\prepared_data\Processor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;

/**
 * The manager class for data processor plugins.
 */
class ProcessorManager extends DefaultPluginManager {

  /**
   * A list of all available processor instances.
   *
   * @var \Drupal\prepared_data\Processor\ProcessorInterface[]
   */
  protected $processors;

  /**
   * A sorted list of all available processor instances.
   *
   * @var \Drupal\prepared_data\Processor\ProcessorInterface[]
   */
  protected $sortedProcessors;

  /**
   * The module settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The Drupal state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * ProcessorManager constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param CacheBackendInterface $cache_backend
   *   The cache backend to use regards discovery.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state system.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, StateInterface $state) {
    parent::__construct('Plugin/prepared_data/Processor', $namespaces, $module_handler, 'Drupal\prepared_data\Processor\ProcessorInterface', 'Drupal\prepared_data\Annotation\PreparedDataProcessor');
    $this->settings = $config_factory->get('prepared_data.settings');
    $this->state = $state;
    $this->alterInfo('prepared_data_processor_info');
    $this->setCacheBackend($cache_backend, 'prepared_data_processor');
  }

  /**
   * Returns a list of all processor instances, sorted by their weight.
   *
   * @return \Drupal\prepared_data\Processor\ProcessorInterface[]
   *   The sorted list of available processor instances.
   */
  public function getAllProcessors() {
    if (!isset($this->sortedProcessors)) {
      $processors = [];
      foreach ($this->getDefinitions() as $definition) {
        $weight = (int) $definition['weight'];
        $i = 0;
        while (TRUE) {
          $i++;
          $weight += ($i / 100);
          if (!isset($processors[$weight])) {
            $processors[$weight] = $this->createInstance($definition['id']);
            break;
          }
        }
      }
      ksort($processors);

      $this->sortedProcessors = array_values($processors);
    }

    return $this->sortedProcessors;
  }

  /**
   * Returns a list of enabled processors, sorted by their weight.
   *
   * @return \Drupal\prepared_data\Processor\ProcessorInterface[]
   *   The sorted list of enabled processor instances.
   */
  public function getEnabledProcessors() {
    $enabled = [];
    foreach ($this->getAllProcessors() as $processor) {
      if ($processor->isEnabled()) {
        $enabled[] = $processor;
      }
    }
    return $enabled;
  }

  /**
   * Returns a list of not enabled processors, sorted by their weight.
   *
   * @return \Drupal\prepared_data\Processor\ProcessorInterface[]
   *   The sorted list of not enabled processor instances.
   */
  public function getNotEnabledProcessors() {
    $not_enabled = [];
    foreach ($this->getAllProcessors() as $processor) {
      if (!$processor->isEnabled()) {
        $not_enabled[] = $processor;
      }
    }
    return $not_enabled;
  }

  /**
   * Returns a list of manageable processors, sorted by their weight.
   *
   * @return \Drupal\prepared_data\Processor\ProcessorInterface[]
   *   The sorted list of manageable processor instances.
   */
  public function getManageableProcessors() {
    $manageable = [];
    foreach ($this->getAllProcessors() as $processor) {
      $definition = $processor->getPluginDefinition();
      if (!empty($definition['manageable'])) {
        $manageable[] = $processor;
      }
    }
    return $manageable;
  }

  /**
   * Makes any processor active which is within its activity restrictions.
   *
   * Controlled batch processes may use this method,
   * while regular requests should not.
   */
  public function acquireActiveProcessors() {
    $enabled_processors = $this->settings->get('enabled_processors');
    foreach ($this->getEnabledProcessors() as $processor) {
      $id = $processor->getPluginId();
      if (!$processor->isActive() && !empty($enabled_processors[$id])) {
        if ($enabled_processors[$id] === 'unlimited') {
          $processor->setActive(TRUE);
          continue;
        }
        list($restricted_amount, $restricted_period) = explode('-', $enabled_processors[$id]);
        $state_id = 'prepared_data.processor_' . $id;
        $processor_state = $this->state->get($state_id, ['activity' => []]);
        $activity = $processor_state['activity'];
        $now = (new \DateTime('now'))->setTimezone(new \DateTimeZone('UTC'))->getTimestamp();
        if (empty($activity['period_begin']) || (($activity['period_begin'] + $restricted_period) < $now)) {
          // Either this is the first time for a processing period,
          // or the last known period state is out of date.
          // Thus, start a new period.
          $activity['period_begin'] = $now;
          $activity['usage_count'] = 0;
        }
        $activity['usage_count']++;
        if (!($activity['usage_count'] > $restricted_amount)) {
          $processor_state['activity'] = $activity;
          $this->state->set($state_id, $processor_state);
          $processor->setActive(TRUE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    if (!isset($this->processors[$plugin_id])) {
      /** @var \Drupal\prepared_data\Processor\ProcessorInterface $instance */
      $instance = parent::createInstance($plugin_id, $configuration);
      $enabled = TRUE;
      $active = TRUE;
      $definition = $instance->getPluginDefinition();
      if (!empty($definition['manageable'])) {
        $enabled_processors = $this->settings->get('enabled_processors');
        // This processor can be managed by an admin,
        // thus check whether it has been enabled or not.
        if (empty($enabled_processors[$plugin_id])) {
          $enabled = FALSE;
        }
        elseif ($enabled_processors[$plugin_id] !== 'unlimited') {
          // This processor is restricted regards its activity.
          // Commands and processes must manually set the active state,
          // depending on the given user-defined restrictions.
          $active = FALSE;
        }
      }
      $instance->setEnabled($enabled);
      $instance->setActive($active);
      $this->processors[$plugin_id] = $instance;
    }
    return $this->processors[$plugin_id];
  }

}
