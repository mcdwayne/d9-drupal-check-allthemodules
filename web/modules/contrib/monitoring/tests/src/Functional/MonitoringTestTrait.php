<?php

namespace Drupal\Tests\monitoring\Functional;

use Drupal\Component\Serialization\Json;

/**
 * Monitoring test helper trait.
 */
trait MonitoringTestTrait {

  /**
   * Executes a sensor and returns the result.
   *
   * @param string $sensor_name
   *   Name of the sensor to execute.
   *
   * @return \Drupal\monitoring\Result\SensorResultInterface
   *   The sensor result.
   */
  protected function runSensor($sensor_name) {
    // Make sure the sensor is enabled.
    monitoring_sensor_manager()->enableSensor($sensor_name);
    return monitoring_sensor_run($sensor_name, TRUE, TRUE);
  }

  /**
   * Install modules and fix test container.
   *
   * @param string[] $module_list
   *   An array of module names.
   * @param bool $enable_dependencies
   *   (optional) If TRUE, dependencies will automatically be installed in the
   *   correct order. This incurs a significant performance cost, so use FALSE
   *   if you know $module_list is already complete.
   *
   * @return bool
   *   FALSE if one or more dependencies are missing, TRUE otherwise.
   *
   * @see \Drupal\Tests\monitoring\Functional\MonitoringTestBase::uninstallModules()
   * @see \Drupal\Core\Extension\ModuleInstallerInterface::install()
   */
  protected function installModules(array $module_list, $enable_dependencies = TRUE) {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_handler */
    $module_handler = \Drupal::service('module_installer');

    // Install the modules requested.
    $return = $module_handler->install($module_list, $enable_dependencies);

    // The container is rebuilt, thus reassign it.
    $this->container = \Drupal::getContainer();

    return $return;
  }

  /**
   * Uninstall modules and fix test container.
   *
   * @param string[] $module_list
   *   The modules to uninstall.
   * @param bool $uninstall_dependents
   *   (optional) If TRUE, dependent modules will automatically be uninstalled
   *   in the correct order. This incurs a significant performance cost, so use
   *   FALSE if you know $module_list is already complete.
   *
   * @return bool
   *   FALSE if one or more dependencies are missing, TRUE otherwise.
   *
   * @see \Drupal\Tests\monitoring\Functional\MonitoringTestBase::installModules()
   * @see \Drupal\Core\Extension\ModuleInstallerInterface::uninstall()
   */
  protected function uninstallModules(array $module_list, $uninstall_dependents = TRUE) {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_handler */
    $module_handler = \Drupal::service('module_installer');

    // Install the modules requested.
    $return = $module_handler->uninstall($module_list, $uninstall_dependents);

    // The container is rebuilt, thus reassign it.
    $this->container = \Drupal::getContainer();

    return $return;
  }

  /**
   * Do the request.
   *
   * @param string $action
   *   Action to perform.
   * @param array $query
   *   Path query key - value pairs.
   *
   * @return array
   *   Decoded json object.
   */
  protected function doJsonRequest($action, $query = array()) {
    $query['_format'] = 'json';
    $this->drupalGet($action, ['query' => $query]);
    return Json::decode((string) $this->getSession()->getPage()->getContent());
  }

}
