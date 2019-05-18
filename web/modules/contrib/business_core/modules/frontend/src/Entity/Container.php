<?php

namespace Drupal\frontend\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\frontend\ContainerInterface;

/**
 * Defines the configured container entity.
 *
 * @ConfigEntityType(
 *   id = "container",
 *   label = @Translation("Container"),
 *   label_collection = @Translation("Containers"),
 *   handlers = {
 *     "list_builder" = "Drupal\frontend\ContainerListBuilder",
 *     "form" = {
 *       "default" = "Drupal\frontend\ContainerForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer containers",
 *   config_prefix = "container",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/container/add",
 *     "delete-form" = "/admin/container/{container}/delete",
 *     "canonical" = "/admin/container/{container}",
 *     "edit-form" = "/admin/container/{container}/edit",
 *     "collection" = "/admin/container",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "components",
 *   },
 * )
 */
class Container extends ConfigEntityBase implements ContainerInterface {

  /**
   * The container ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Name of the container.
   *
   * @var string
   */
  protected $label;

  protected $components;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('frontend.container.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

}
