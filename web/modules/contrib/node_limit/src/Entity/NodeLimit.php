<?php

namespace Drupal\node_limit\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\node_limit\NodeLimitInterface;

/**
 * Defines the NodeLimit entity.
 *
 * @ConfigEntityType(
 *   id = "node_limit",
 *   label = @Translation("NodeLimit"),
 *   handlers = {
 *     "list_builder" = "Drupal\node_limit\Controller\NodeLimitListBuilder",
 *     "form" = {
 *       "add" = "Drupal\node_limit\Form\NodeLimitForm",
 *       "edit" = "Drupal\node_limit\Form\NodeLimitForm",
 *       "delete" = "Drupal\node_limit\Form\NodeLimitDeleteForm"
 *     }
 *   },
 *   config_prefix = "node_limit",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "entity.node_limit.edit_form",
 *     "delete-form" = "entity.node_limit.delete_form"
 *   }
 * )
 */
class NodeLimit extends ConfigEntityBase implements NodeLimitInterface {

  /**
   * The NodeLimit ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The NodeLimit label.
   *
   * @var string
   */
  protected $label;

}
