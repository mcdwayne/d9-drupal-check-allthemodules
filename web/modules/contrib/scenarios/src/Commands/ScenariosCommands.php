<?php
namespace Drupal\scenarios\Commands;

use Drupal\scenarios\ScenariosHandler;
use Drush\Commands\DrushCommands;

class ScenariosCommands extends DrushCommands {

  /**
   * Drupal\scenarios\ScenariosHandler.
   *
   * @var \Drupal\scenarios\ScenariosHandler
   */
  protected $scenariosHandler;

  /**
   * ScenariosCommands constructor.
   *
   * @param \Drupal\scenarios\ScenariosHandler $scenarios_handler
   *   The update manager service.
   */
  public function __construct(ScenariosHandler $scenarios_handler) {
    $this->scenariosHandler = $scenarios_handler;
  }

  /**
   * Enables a scenario.
   *
   * @command scenarios:enable
   * @param string $scenario
   *   The machine name of the scenario.
   * @usage es dfs_tec
   *   Enables a scenario.
   * @aliases enable-scenario,es
   */
  public function enable($scenario) {
    $this->scenariosHandler->scenarioEnable($scenario);
  }

  /**
   * Uninstalls a scenario.
   *
   * @command scenarios:uninstall
   * @param string $scenario
   *   The machine name of the scenario.
   * @usage us dfs_tec
   *   Uninstalls a scenario.
   * @aliases uninstall-scenario,us
   */
  public function uninstall($scenario) {
    $this->scenariosHandler->scenarioUninstall($scenario);
  }

  /**
   * Resets a scenario.
   *
   * @command scenarios:reset
   * @param string $scenario
   *   The machine name of the scenario.
   * @usage res dfs_tec
   *   Resets a scenario.
   * @aliases reset-scenario,res
   */
  public function reset($scenario) {
    $this->scenariosHandler->scenarioReset($scenario);
  }

}
