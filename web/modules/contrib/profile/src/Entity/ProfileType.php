<?php

namespace Drupal\profile\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the profile type entity class.
 *
 * @ConfigEntityType(
 *   id = "profile_type",
 *   label = @Translation("Profile type"),
 *   label_collection = @Translation("Profile types"),
 *   label_singular = @Translation("profile type"),
 *   label_plural = @Translation("profile types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count profile type",
 *     plural = "@count profile types",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\profile\ProfileTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\profile\Form\ProfileTypeForm",
 *       "add" = "Drupal\profile\Form\ProfileTypeForm",
 *       "edit" = "Drupal\profile\Form\ProfileTypeForm",
 *       "duplicate" = "Drupal\profile\Form\ProfileTypeForm",
 *       "delete" = "Drupal\profile\Form\ProfileTypeDeleteForm"
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer profile types",
 *   config_prefix = "type",
 *   bundle_of = "profile",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "multiple",
 *     "registration",
 *     "roles",
 *     "use_revisions",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/people/profile-types/add",
 *     "edit-form" = "/admin/config/people/profile-types/manage/{profile_type}",
 *     "duplicate-form" = "/admin/config/people/profile-types/manage/{profile_type}/duplicate",
 *     "delete-form" = "/admin/config/people/profile-types/manage/{profile_type}/delete",
 *     "collection" = "/admin/config/people/profile-types"
 *   }
 * )
 */
class ProfileType extends ConfigEntityBundleBase implements ProfileTypeInterface {

  /**
   * The profile type ID.
   *
   * @var int
   */
  protected $id;

  /**
   * The profile type label.
   *
   * @var string
   */
  protected $label;

  /**
   * Whether a user can have multiple profiles of this type.
   *
   * @var bool
   */
  protected $multiple = FALSE;

  /**
   * Whether a profile of this type should be created during registration.
   *
   * @var bool
   */
  protected $registration = FALSE;

  /**
   * The user roles allowed to have profiles of this type.
   *
   * @var array
   */
  protected $roles = [];

  /**
   * Should profiles of this type always generate revisions.
   *
   * @var bool
   */
  protected $use_revisions = FALSE;

  /**
   * {@inheritdoc}
   */
  public function allowsMultiple() {
    return $this->multiple;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple($multiple) {
    $this->multiple = $multiple;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistration() {
    return $this->registration;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegistration($registration) {
    $this->registration = $registration;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function setRoles(array $roles) {
    $this->roles = $roles;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->use_revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Rebuild module data to generate bundle permissions and link tasks.
    if (!$update) {
      system_rebuild_module_data();
    }
  }

}
