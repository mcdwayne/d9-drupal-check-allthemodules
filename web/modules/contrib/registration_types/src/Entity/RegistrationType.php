<?php

namespace Drupal\registration_types\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Cache\Cache;

/**
 * Defines the Registration type entity.
 *
 * @ConfigEntityType(
 *   id = "registration_type",
 *   label = @Translation("Registration type"),
 *   handlers = {
 *     "list_builder" = "Drupal\registration_types\RegistrationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\registration_types\Form\RegistrationTypeForm",
 *       "edit" = "Drupal\registration_types\Form\RegistrationTypeForm",
 *       "delete" = "Drupal\registration_types\Form\RegistrationTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\registration_types\RegistrationTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "registration_type",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/people/registration-types/{registration_type}",
 *     "add-form" = "/admin/config/people/registration-types/add",
 *     "edit-form" = "/admin/config/people/registration-types/{registration_type}/edit",
 *     "delete-form" = "/admin/config/people/registration-types/{registration_type}/delete",
 *     "collection" = "/admin/config/people/registration-types"
 *   }
 * )
 */
class RegistrationType extends ConfigEntityBase implements RegistrationTypeInterface {

  /**
   * The Registration type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Registration type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Registration type enabled flag.
   *
   * @var boolean
   */
  protected $enabled;

  /**
   * The Registration type custom path.
   *
   * @var string
   */
  protected $custom_path;

  /**
   * The Registration type tab title.
   *
   * @var string
   */
  protected $tab_title;

  /**
   * The Registration type user form display mode.
   *
   * @var string
   */
  protected $display;

  /**
   * The Registration type page title.
   *
   * @var string
   */
  protected $page_title;

  /**
   * The registration type administrative description.
   *
   * @var string
   */
  protected $description;

  /**
   * Which roles a user will be assigned at registration.
   *
   * @var array
   */
  protected $roles = [];

  /**
   * {@inheritdoc}
   */
  public function getEnabled() {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled($enabled) {
    $this->enabled = $enabled;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomPath() {
    return $this->custom_path;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomPath($custom_path) {
    $this->custom_path = $custom_path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTabTitle() {
    return $this->tab_title;
  }

  /**
   * {@inheritdoc}
   */
  public function setTabTitle($tab_title) {
    $this->tab_title = $tab_title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplay() {
    return $this->display;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplay($display) {
    $this->display = $display;
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
  public function setRoles($roles) {
    $this->roles = $roles;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPageTitle() {
    return $this->page_title;
  }

  /**
   * {@inheritdoc}
   */
  public function setPageTitle($page_title) {
    $this->page_title = $page_title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   *  @todo: implement also postDelete method to clear cache
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    if (!$update) {
      // reset cache to invoke hook_entity_type_build()
      $this->entityTypeManager()->clearCachedDefinitions();
    }
    else {
      // @todo: rebuild route cache only if isNew, deleted or changed route path (when implemented)
    }
    // rebuild route cache for newly added registration types
    // or for enabled/disabled registration types (or edited registration type path)
    \Drupal::service("router.builder")->rebuild();
    // clear registration form cache (@see RegistrationTypeRegisterForm.php)
    Cache::invalidateTags(['registration_type']);
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    \Drupal::service("router.builder")->rebuild();
    Cache::invalidateTags(['registration_type']);
    parent::postDelete($storage, $entities);
  }

}
