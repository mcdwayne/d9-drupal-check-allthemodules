<?php

namespace Drupal\entity_collector\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Entity collection type entity.
 *
 * @ConfigEntityType(
 *   id = "entity_collection_type",
 *   label = @Translation("Entity collection type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\entity_collector\EntityCollectionTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_collector\Form\EntityCollectionTypeForm",
 *       "edit" = "Drupal\entity_collector\Form\EntityCollectionTypeForm",
 *       "delete" = "Drupal\entity_collector\Form\EntityCollectionTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "entity_collection_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "entity_collection",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/collections/{entity_collection_type}",
 *     "add-form" = "/admin/structure/collections/add",
 *     "edit-form" = "/admin/structure/collections/{entity_collection_type}/edit",
 *     "delete-form" = "/admin/structure/collections/{entity_collection_type}/delete",
 *     "collection" = "/admin/structure/collections"
 *   }
 * )
 */
class EntityCollectionType extends ConfigEntityBundleBase implements EntityCollectionTypeInterface {

  /**
   * The Entity collection type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Entity collection type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The entity source ID.
   *
   * @var string
   */
  protected $source;

  /**
   * The entity source field name.
   *
   * @var string
   */
  protected $source_field_name;

  /**
   * {@inheritdoc}
   */
  public function getSource(){
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source){
    return $this->set('source', $source);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldName(){
    return $this->source_field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceFieldName($source_field_name){
    return $this->set('source_field_name', $source_field_name);
  }
}
