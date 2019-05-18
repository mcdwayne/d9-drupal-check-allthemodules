<?php

namespace Drupal\daemons;

use Drupal\Component\Datetime\Time;
use Drupal\Core\State\State;

/**
 * Manages daemons.
 */
class DaemonManager {

  /**
   * The State object.
   *
   * @var \Drupal\Core\State\State
   */
  private $state;

  /**
   * The Time object.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  private $time;

  /**
   * The Plugin object.
   *
   * @var \Drupal\daemons\PluginDaemonManager
   */
  private $plugin;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\State\State $state
   *   The state key-value store service.
   * @param \Drupal\Component\Datetime\Time $time
   *   Time object.
   * @param \Drupal\daemons\PluginDaemonManager $plugin
   *   Plugin Daemon Manager.
   */
  public function __construct(State $state, Time $time, PluginDaemonManager $plugin) {
    $this->state = $state;
    $this->time = $time;
    $this->plugin = $plugin;
  }

  /**
   * Execute task for daemon.
   */
  public function daemonExecute($task, $daemon_id) {
    switch ($task) {
      case 'start':
        $this->start($daemon_id);
        break;

      case 'stop':
        $this->stop($daemon_id);
        break;

      case 'forceStop':
        $this->forceStop($daemon_id);
        break;

      case 'restart':
        $this->restart($daemon_id);
        break;

      case 'clear':
        $this->clear($daemon_id);
        break;

      default:
    }
  }

  /**
   * Start daemon.
   */
  protected function start($daemon_id) {
    $command = 'drupal daemons:run ' . $daemon_id . ' >/dev/null 2>&1 & echo $!; ';
    shell_exec($command);
  }

  /**
   * Soft Stop daemon.
   */
  protected function stop($daemon_id) {
    $command = 'drupal daemons:stop ' . $daemon_id;
    shell_exec($command);
  }

  /**
   * Hard Stop daemon.
   */
  protected function forceStop($daemon_id) {
    $command = 'drupal daemons:stop ' . $daemon_id . ' force';
    shell_exec($command);
  }

  /**
   * Restart daemon.
   */
  protected function restart($daemon_id) {
    $command = 'drupal daemons:restart ' . $daemon_id;
    shell_exec($command);
  }

  /**
   * Clear state data.
   */
  protected function clear($daemon_id) {
    $this->state->set($daemon_id, '');
  }

  /**
   * Check if the daemon is broken.
   *
   * @param string $daemon_id
   *   The daemon name.
   *
   * @return bool
   *   True if the daemon is broken.
   */
  public function isBroken(string $daemon_id) {
    // Current time.
    $current_time = $this->time->getCurrentTime();

    // Get daemon frequency.
    $daemon_definition = $this->plugin->getDefinition($daemon_id);
    $frequency = $daemon_definition['periodicTimer'];

    // Get daemon data.
    $data = $this->getDaemonData($daemon_id);
    if (!empty($data['processId'])
      && (empty($data['lastRunTime'])
        || ($current_time - $frequency) > $data['lastRunTime'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get stored daemon data.
   *
   * @param string $daemon_id
   *   The daemon name.
   *
   * @return array
   *   The data daemon.
   */
  public function getDaemonData(string $daemon_id) {
    $data = $this->state->get($daemon_id);
    $data = explode(',', $data);
    $data = array_pad($data, 3, 0);
    list($daemonId, $processId, $lastRunTime) = $data;

    return [
      'daemonId' => $daemonId,
      'processId' => $processId,
      'lastRunTime' => $lastRunTime,
    ];
  }

}
