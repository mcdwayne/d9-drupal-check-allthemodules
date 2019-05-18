<?php

/**
 * @file
 * Contains Drupal\environment\Entity\Environment.
 */

namespace Drupal\environment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\environment\EnvironmentInterface;

/**
 * Defines the Environment entity.
 *
 * @ConfigEntityType(
 *   id = "environment",
 *   label = @Translation("Environment"),
 *   handlers = {
 *     "list_builder" = "Drupal\environment\Controller\EnvironmentListBuilder",
 *     "form" = {
 *       "add" = "Drupal\environment\Form\EnvironmentForm",
 *       "edit" = "Drupal\environment\Form\EnvironmentForm",
 *       "delete" = "Drupal\environment\Form\EnvironmentDeleteForm"
 *     }
 *   },
 *   config_prefix = "environment",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/environment/{environment}",
 *     "edit-form" = "/admin/structure/environment/{environment}/edit",
 *     "delete-form" = "/admin/structure/environment/{environment}/delete",
 *     "collection" = "/admin/structure/environment"
 *   }
 * )
 */
class Environment extends ConfigEntityBase implements EnvironmentInterface {
  /**
   * The Environment ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Environment label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Environment description.
   *
   * @var string
   */
  protected $description;

  public function getDescription() {
    return $this->description;
  }

}
