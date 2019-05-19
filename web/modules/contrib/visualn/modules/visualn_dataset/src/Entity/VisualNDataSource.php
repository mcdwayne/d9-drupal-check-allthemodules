<?php

namespace Drupal\visualn_dataset\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the VisualN Data Source entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_data_source",
 *   label = @Translation("VisualN Data Source"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn_dataset\VisualNDataSourceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn_dataset\Form\VisualNDataSourceForm",
 *       "edit" = "Drupal\visualn_dataset\Form\VisualNDataSourceForm",
 *       "delete" = "Drupal\visualn_dataset\Form\VisualNDataSourceDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn_dataset\VisualNDataSourceHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_data_source",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "resource_provider_id" = "resource_provider_id",
 *     "resource_provider_config" = "resource_provider_config"
 *   },
 *   links = {
 *     "canonical" = "/admin/visualn/data-sources/manage/{visualn_data_source}",
 *     "add-form" = "/admin/visualn/data-sources/add",
 *     "edit-form" = "/admin/visualn/data-sources/manage/{visualn_data_source}/edit",
 *     "delete-form" = "/admin/visualn/data-sources/manage/{visualn_data_source}/delete",
 *     "collection" = "/admin/visualn/data-sources"
 *   }
 * )
 */
class VisualNDataSource extends ConfigEntityBase implements VisualNDataSourceInterface {

  /**
   * The VisualN Data Source ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN Data Source label.
   *
   * @var string
   */
  protected $label;

  /**
   * The VisualN resource provider ID.
   *
   * @var string
   */
  protected $resource_provider_id;

  /**
   * The VisualN resource provider config.
   *
   * @var array
   */
  protected $resource_provider_config = [];

  /**
   * The VisualN source specific resource provider plugin.
   *
   * @var \Drupal\visualn_dataset\Plugin\VisualNResourceProviderInterface
   */
  protected $resource_provider_plugin;

  /**
   * {@inheritdoc}
   *
   * @todo: add description
   */
  public function getResourceProviderId() {
    return $this->resource_provider_id ?: '';
  }

  /**
   * {@inheritdoc}
   *
   * @todo: add description
   */
  public function getResourceProviderPlugin() {
    if (!isset($this->resource_provider_plugin)) {
      $resource_provider_id = $this->getResourceProviderId();
      if (!empty($resource_provider_id)) {
        $resource_provider_config = [];
        $resource_provider_config = $this->getResourceProviderConfig() + $resource_provider_config;
        // @todo: load manager at object instantiation
        $this->resource_provider_plugin = \Drupal::service('plugin.manager.visualn.resource_provider')->createInstance($resource_provider_id, $resource_provider_config);
      }
    }

    return $this->resource_provider_plugin;
  }


  /**
   * {@inheritdoc}
   */
  public function getResourceProviderConfig() {
    return $this->resource_provider_config;
  }

  /**
   * {@inheritdoc}
   */
  public function setResourceProviderConfig($resource_provider_config) {
    $this->resource_provider = $resource_provider_config;
    return $this;
  }

}
