<?php

namespace Drupal\white_label_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the While entity type entity.
 *
 * @ConfigEntityType(
 *   id = "while_entity_type",
 *   label = @Translation("While entity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\white_label_entity\WhileEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\white_label_entity\Form\WhileEntityTypeForm",
 *       "edit" = "Drupal\white_label_entity\Form\WhileEntityTypeForm",
 *       "delete" = "Drupal\white_label_entity\Form\WhileEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\white_label_entity\WhileEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "while_entity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "while_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class WhileEntityType extends ConfigEntityBundleBase implements WhileEntityTypeInterface {

  /**
   * The While entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The While entity type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The state of entity page display.
   *
   * @var bool
   */
  protected $entity_pages_active = 0;

  /**
   * {@inheritdoc}
   */
  public function getEntityPagesActive() {
    return $this->get('entity_pages_active');
  }

}
