<?php

namespace Drupal\personas\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\personas\PersonaInterface;

/**
 * Defines the Persona entity.
 *
 * @ConfigEntityType(
 *   id = "persona",
 *   label = @Translation("Persona"),
 *   handlers = {
 *     "access" = "Drupal\personas\PersonaAccessControlHandler",
 *     "list_builder" = "Drupal\personas\PersonaListBuilder",
 *     "form" = {
 *       "default" = "Drupal\personas\Form\PersonaForm",
 *       "delete" = "Drupal\personas\Form\PersonaDeleteForm"
 *     },
 *   },
 *   config_prefix = "persona",
 *   admin_permission = "administer personas",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/admin/people/personas/manage/{persona}",
 *     "delete-form" = "/admin/people/personas/manage/{persona}/delete",
 *     "collection" = "/admin/people/personas"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "roles",
 *   }
 * )
 */
class Persona extends ConfigEntityBase implements PersonaInterface {

  /**
   * The Persona ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Persona label.
   *
   * @var string
   */
  protected $label;

  /**
   * The roles belonging to this persona.
   *
   * @var array
   */
  protected $roles = [];

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return array_filter($this->roles);
  }

  /**
   * {@inheritdoc}
   */
  public function hasRole($role) {
    return in_array($role, $this->roles);
  }

  /**
   * {@inheritdoc}
   */
  public function addRole($role) {
    if (!$this->hasRole($role)) {
      $this->roles[] = $role;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole($role) {
    $this->roles = array_diff($this->roles, [$role]);
    return $this;
  }

}
