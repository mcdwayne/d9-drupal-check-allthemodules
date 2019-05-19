<?php

namespace Drupal\svg_maps\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\ConfigEntityType;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Defines the Svg maps entity entity.
 *
 * @ConfigEntityType(
 *   id = "svg_maps_entity",
 *   label = @Translation("Svg maps entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\svg_maps\SvgMapsEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\svg_maps\Form\SvgMapsEntityForm",
 *       "edit" = "Drupal\svg_maps\Form\SvgMapsEntityForm",
 *       "delete" = "Drupal\svg_maps\Form\SvgMapsEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\svg_maps\SvgMapsEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "svg_maps_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "type" = "type",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/svg_maps_entity/{svg_maps_entity}",
 *     "add-form" = "/admin/structure/svg_maps_entity/add",
 *     "edit-form" = "/admin/structure/svg_maps_entity/{svg_maps_entity}/edit",
 *     "delete-form" = "/admin/structure/svg_maps_entity/{svg_maps_entity}/delete",
 *     "collection" = "/admin/structure/svg_maps_entity"
 *   }
 * )
 */
class SvgMapsEntity extends ConfigEntityBase implements SvgMapsEntityInterface {

  /**
   * The Svg maps entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Svg maps entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Svg maps entity type.
   *
   * @var string
   */
  protected $type = 'generic';

  /**
   * The Svg maps path.
   *
   * @var array
   */
  protected $maps_path = [];

  /**
   * The type plugin configuration.
   *
   * @var array
   */
  public $type_configuration = [];

  /**
   * Type lazy plugin collection.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $typePluginCollection;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type . '.' . preg_replace('/[^a-z0-9]/i', '_', strtolower($this->label));
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->typePluginCollection()->get($this->type);
  }

  /**
   * Gets the plugin collections used by this object.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection[]
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getPluginCollections() {
    return ['type_configuration' => $this->typePluginCollection()];
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeConfiguration() {
    return $this->type_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setTypeConfiguration($configuration) {
    $this->type_configuration = $configuration;
    $this->typePluginCollection = NULL;
  }

  /**
   * Returns type lazy plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *   The type configuration.
   */
  protected function typePluginCollection() {
    if (!$this->typePluginCollection) {
      $this->typePluginCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.svg_maps.type'), $this->type, $this->type_configuration);
    }
    return $this->typePluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->get('plugin');
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin) {
    $this->set('plugin', $plugin);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapsPath() {
    return $this->get('maps_path');
  }

  /**
   * {@inheritdoc}
   */
  public function setMapsPath(array $paths) {
    $this->set('maps_path', $paths);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDetailedPath() {
    return $this->get('detailed_path');
  }

  /**
   * {@inheritdoc}
   */
  public function setDetailedPath(array $paths) {
    $this->set('detailed_path', $paths);
    return $this;
  }
  /**
   * Loads
   * @param string $type
   *   The plugin type id.
   * @param string $label
   *   Label of the svg map entity.
   *
   * @return static
   *   The svg map config entity if one exists for the provided label,
   *   otherwise NULL.
   */
  public static function loadByName($type, $label) {
    return \Drupal::entityManager()->getStorage('svg_maps_entity')->load($type . '.' . $label);
  }

}
