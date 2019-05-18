<?php

namespace Drupal\daemons;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\State;

/**
 * Defines a daemon plugin base class.
 *
 * @see \Drupal\daemons\DaemonInterface
 */
abstract class DaemonPluginBase extends PluginBase implements DaemonInterface, ContainerFactoryPluginInterface {

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, State $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  /**
   * Get plugin id.
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * Get daemon name.
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Get periodic timer value.
   */
  public function getPeriodicTimer() {
    $definition = $this->pluginDefinition;
    return isset($definition['periodicTimer']) ? $definition['periodicTimer'] : '';
  }

  /**
   * Get daemon status.
   */
  public function getStatus() {
    return $this->getProcessId() ? 1 : 0;
  }

  /**
   * Get daemon pid.
   */
  public function getProcessId() {
    $data = $this->getDaemonData();
    return !empty($data['processId']) ? $data['processId'] : 0;
  }

  /**
   * Get stored daemon data.
   *
   * @return array
   *   The data daemon.
   */
  public function getDaemonData() {
    $data = $this->state->get($this->getId());
    $data = explode(',', $data);
    $data = array_pad($data, 3, 0);
    list($daemonId, $processId, $lastRunTime) = $data;

    return [
      'daemonId' => $daemonId,
      'processId' => $processId,
      'lastRunTime' => $lastRunTime,
    ];
  }

  /**
   * Store daemon data.
   *
   * @param string $pid
   *   Php process id.
   */
  public function storeDaemonData($pid) {
    $plugin_id = $this->getId();

    $data = [
      'daemonId' => $plugin_id,
      'processId' => $pid,
      'lastRunTime' => \Drupal::time()->getCurrentTime(),
    ];
    $this
      ->state
      ->set($plugin_id, implode(',', $data));
  }

  /**
   * {@inheritdoc}
   */
  public function checkDaemonProcessId() {
    // Get current process id.
    $current_pid = getmypid();
    // Get stored process id.
    $stored_pid = $this->getProcessId();
    // Kill duplicate process of daemon.
    if ($stored_pid != $current_pid) {
      shell_exec("kill -9 $current_pid");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateLastRunTime() {
    // Get all data.
    $plugin_id = $this->getId();
    $data = $this->getDaemonData();

    // Update time.
    $data['lastRunTime'] = \Drupal::time()->getCurrentTime();

    // Set new data.
    $this
      ->state
      ->set($plugin_id, implode(',', $data));
  }

  /**
   * {@inheritdoc}
   */
  abstract public function execute($loop);

}
