<?php

namespace Drupal\analytics\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\analytics\AnalyticsInstanceInterface;

/**
 * Defines the analytics instance entity.
 *
 * @ConfigEntityType(
 *   id = "analytics_instance",
 *   label = @Translation("Analytics Service"),
 *   handlers = {
 *     "list_builder" = "Drupal\analytics\AnalyticsInstanceListBuilder",
 *     "form" = {
 *       "default" = "Drupal\analytics\Form\AnalyticsInstanceForm",
 *       "add" = "Drupal\analytics\Form\AnalyticsInstanceForm",
 *       "edit" = "Drupal\analytics\Form\AnalyticsInstanceForm",
 *       "delete" = "Drupal\analytics\Form\AnalyticsInstanceDeleteForm",
 *     }
 *   },
 *   config_prefix = "analytics_instance",
 *   admin_permission = "administer analytics",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/services/analytics/add",
 *     "edit-form" = "/admin/config/services/analytics/{analytics_instance}",
 *     "delete-form" = "/admin/config/services/analytics/{analytics_instance}/delete",
 *     "collection" = "/admin/config/services/analytics",
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "type_id",
 *     "type_settings"
 *   }
 * )
 */
class AnalyticsInstance extends ConfigEntityBase implements AnalyticsInstanceInterface {


  /**
   * The analytics instance ID.
   *
   * @var string
   */
  public $id;

  /**
   * The analytics instance label.
   *
   * @var string
   */
  public $label;

  /**
   * The description of the analytics instance.
   *
   * @var string
   */
  public $description;

  /**
   * The name of the service.
   *
   * @var string
   */
  public $service;

  /**
   * An indicator whether the service is locked.
   *
   * @var bool
   */
  public $locked;

  /**
   * The name of the service.
   *
   * @var array
   */
  public $options = array();

  /**
   * The analytics service plugin ID.
   *
   * @var string
   */
  public $type_id;

  /**
   * Analytics service settings.
   *
   * An array of key/value pairs.
   *
   * @var array
   */
  public $type_settings = [];

  /**
   * {@inheritdoc}
   */
  public function getTypeId() {
    return $this->type_id;
  }

  /**
   * Gets the analytics service plugin manager.
   *
   * @return \Drupal\analytics\AnalyticsService\AnalyticsServiceManager
   */
  protected function analyticsServiceManager() {
    return \Drupal::service('plugin.manager.analytics.service');
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeSetting($key, $default = NULL) {
    if (isset($this->type_settings[$key])) {
      return $this->type_settings[$key];
    }
    else {
      return $default;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeSettings() {
    return $this->type_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypePlugin() {
    if ($plugin_id = $this->getTypeId()) {
      return $this->analyticsServiceManager()->createInstance($plugin_id, $this->getTypeSettings());
    }
  }
}


