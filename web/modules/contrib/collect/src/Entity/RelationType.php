<?php
/**
 * @file
 * Contains \Drupal\collect\Entity\RelationType.
 */

namespace Drupal\collect\Entity;

use Drupal\collect\Relation\RelationTypeInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Models an assignment of a relation plugin to a relation URI space.
 *
 * @todo Sort out "relation/ship" terminology and decide a name for this entity type.
 *
 * @ConfigEntityType(
 *   id = "collect_relation_type",
 *   label = @Translation("Relation type"),
 *   admin_permission = "administer collect",
 *   config_prefix = "relation_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uri_pattern",
 *     "plugin_id",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\collect\Relation\RelationTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\collect\Form\RelationTypeForm",
 *       "delete" = "Drupal\collect\Form\RelationTypeDeleteForm",
 *     },
 *   },
 *   links = {
 *     "collection" = "/admin/structure/collect/relation",
 *     "add-form" = "/admin/structure/collect/relation/add",
 *     "edit-form" = "/admin/structure/collect/relation/manage/{collect_relation_type}",
 *     "delete-form" = "/admin/structure/collect/relation/manage/{collect_relation_type}/delete",
 *   }
 * )
 */
class RelationType extends ConfigEntityBase implements RelationTypeInterface {

  /**
   * The ID of the relation type.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the relation type.
   *
   * @var string
   */
  protected $label;

  /**
   * The relation URI pattern to assign the plugin to.
   *
   * @var string
   */
  protected $uri_pattern;

  /**
   * The ID of the assigned relation plugin.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * {@inheritdoc}
   */
  public function getUriPattern() {
    return $this->uri_pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setUriPattern($uri_pattern) {
    $this->uri_pattern = $uri_pattern;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin_id = $plugin_id;
    return $this->plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->calculatePluginDependencies(collect_relation_manager()->createInstance($this->getPluginId()));
    return $this->dependencies;
  }

}
