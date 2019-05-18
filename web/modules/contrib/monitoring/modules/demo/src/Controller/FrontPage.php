<?php
/**
 * @file
 * Contains \Drupal\monitoring_demo\Controller\FrontPage.
 */

namespace Drupal\monitoring_demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Simple front page controller for the monitoring_demo module.
 */
class FrontPage extends ControllerBase {

  public function content() {
    return array(
      'intro' => array(
        '#markup' => '<p>' . t('Welcome to the Monitoring demo installation. Content and log messages (including dummy errors) have been generated automatically to demonstrate different sensors and their escalation.'),
      ),
      'report' => array(
        '#type' => 'item',
        '#title' => \Drupal::l(t('Monitoring sensors overview'), Url::fromRoute('monitoring.sensor_list')),
        '#description' => t('Basic dashboard showing the sensor list with their status and information.'),
        '#description_display' => 'after',
      ),
      'configuration' => array(
        '#type' => 'item',
        '#title' =>\Drupal::l(t('Monitoring sensors settings'), Url::fromRoute('monitoring.sensors_overview_settings')),
        '#description' => t('Monitoring sensors configuration page. See this page for the complete list of the available sensors.'),
        '#description_display' => 'after',
      ),
      'sensor_enabled_modules' => array(
        '#type' => 'item',
        '#title' => t('Sensor example: "Installed modules"'),
        '#description' => t('Monitors which modules are supposed to be installed. In case there is a needed module uninstalled or excess module installed you will be noticed.'),
        '#description_display' => 'after',
        'list' => array(
          '#theme' => 'item_list',
          '#items' => array(
            t('<a href="@url">Configure</a> the module by submitting the default settings.', array('@url' => Url::fromRoute('entity.monitoring_sensor_config.details_form', array('monitoring_sensor_config' => 'monitoring_installed_modules'))->toString())),
            t('<a href="@url">Uninstall</a> Dashboard module and install Book module.', array('@url' => Url::fromRoute('system.modules_list')->toString())),
            t('Visit the <a href="@url">sensors overview page</a> to see the reported issue.', array('@url' => Url::fromRoute('monitoring.sensor_list')->toString())),
          )
        ),
      ),
      'sensor_disappeared_sensors' => array(
        '#type' => 'item',
        '#title' => t('Sensor example: "Disappeared sensors"'),
        '#description' => t('Additionally to disabling modules, configuration changes like removing content types or search API indexes could lead to sensors that silently disappear.'),
        '#description_display' => 'after',
        'list' => array(
          '#theme' => 'item_list',
          '#items' => array(
            t('<a href="@url">Uninstall</a> the Database logging module what will make all the watchdog related sensors disappear.',
              array('@url' => \Drupal::url('system.modules_uninstall'))),
            t('Visit the <a href="@url">sensors overview page</a> to see the sensor reporting disappeared sensors.', array('@url' => \Drupal::url('monitoring.sensor_list'))),
          )
        ),
      ),
      'integrations' => array(
        '#type' => 'item',
        '#title' => t('Integrations'),
        'list' => array(
          '#theme' => 'item_list',
          '#items' => array(
            t('Drush integration - open up your console and type in # drush monitoring-sensor-config or # drush monitoring-run. See the drush help for more info and commands.'),
            t('REST resource for both the info about sensors and running the sensors via the service. Open up your REST client and visit /monitoring-sensor/{sensor_name} and /monitoring-sensor-result/{sensor_name}'),
          )
        ),
      ),
    );
  }
}
