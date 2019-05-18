<?php

namespace Drupal\bibcite_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Contributor role entity.
 *
 * @ConfigEntityType(
 *   id = "bibcite_contributor_role",
 *   label = @Translation("Contributor role"),
 *   handlers = {
 *     "list_builder" = "Drupal\bibcite_entity\ContributorRoleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bibcite_entity\Form\ContributorRoleForm",
 *       "edit" = "Drupal\bibcite_entity\Form\ContributorRoleForm",
 *       "delete" = "Drupal\bibcite_entity\Form\ContributorRoleDeleteForm"
 *     },
 *   },
 *   config_prefix = "bibcite_contributor_role",
 *   admin_permission = "administer bibcite_reference",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/bibcite/settings/contributor/role/add",
 *     "edit-form" = "/admin/config/bibcite/settings/contributor/role/{bibcite_contributor_role}",
 *     "delete-form" = "/admin/config/bibcite/settings/contributor/role/{bibcite_contributor_role}/delete",
 *     "collection" = "/admin/config/bibcite/settings/contributor/role"
 *   }
 * )
 */
class ContributorRole extends ConfigEntityBase implements ContributorRoleInterface {

  /**
   * The Contributor role ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Contributor role label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Contributor role weight.
   *
   * @var string
   */
  protected $weight;

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    return $this->set('weight', $weight);
  }

}
