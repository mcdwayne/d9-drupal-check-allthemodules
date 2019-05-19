<?php

namespace Drupal\transcoding\Entity;

use Drupal;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\transcoding\Plugin\TranscodingServicePluginCollection;
use Drupal\transcoding\TranscodingServiceInterface;

/**
 * Defines the Transcoding service entity.
 *
 * @ConfigEntityType(
 *   id = "transcoding_service",
 *   label = @Translation("Transcoding service"),
 *   handlers = {
 *     "list_builder" = "Drupal\transcoding\TranscodingServiceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\transcoding\Form\TranscodingServiceForm",
 *       "edit" = "Drupal\transcoding\Form\TranscodingServiceForm",
 *       "delete" = "Drupal\transcoding\Form\TranscodingServiceDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\transcoding\TranscodingServiceHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "transcoding_service",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "status",
 *     "plugin",
 *     "configuration",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/transcoding/service/transcoding_service/{transcoding_service}",
 *     "add-form" = "/admin/config/transcoding/service/transcoding_service/add",
 *     "edit-form" = "/admin/config/transcoding/service/transcoding_service/{transcoding_service}/edit",
 *     "delete-form" = "/admin/config/transcoding/service/transcoding_service/{transcoding_service}/delete",
 *     "collection" = "/admin/config/transcoding/service/transcoding_service"
 *   }
 * )
 */
class TranscodingService extends ConfigEntityBase implements TranscodingServiceInterface {

  /**
   * The Transcoding service ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Transcoding service label.
   *
   * @var string
   */
  protected $label;

  /**
   * The plugin collection.
   *
   * @var \Drupal\transcoding\Plugin\TranscodingServicePluginCollection
   */
  protected $pluginCollection;

  protected $configuration = [];

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin = $plugin_id;
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPluginCollections() {
    return [
      'configuration' => $this->getPluginCollection(),
    ];
  }

  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $plugin_manager = Drupal::service('plugin.manager.transcoder');
      $this->pluginCollection = new TranscodingServicePluginCollection($plugin_manager, $this->plugin, $this->configuration);
    }
    return $this->pluginCollection;
  }

}
