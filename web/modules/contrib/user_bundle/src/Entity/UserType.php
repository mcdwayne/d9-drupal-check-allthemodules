<?php

namespace Drupal\user_bundle\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user_bundle\UserTypeInterface;

/**
 * Defines the User type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "user_type",
 *   label = @Translation("Account type"),
 *   label_collection = @Translation("Account types"),
 *   label_singular = @Translation("account type"),
 *   label_plural = @Translation("account types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count account type",
 *     plural = "@count account types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\user_bundle\UserTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\user_bundle\UserTypeForm",
 *       "edit" = "Drupal\user_bundle\UserTypeForm",
 *       "delete" = "Drupal\user_bundle\Form\UserTypeDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\user_bundle\UserTypeListBuilder",
 *   },
 *   admin_permission = "administer account types",
 *   bundle_of = "user",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/people/types/{user_type}",
 *     "delete-form" = "/admin/config/people/types/{user_type}/delete",
 *     "collection" = "/admin/config/people/types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   }
 * )
 */
class UserType extends ConfigEntityBundleBase implements UserTypeInterface {

  /**
   * The machine name of this user type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the user type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this user type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('user.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
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
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update && $this->getOriginalId() != $this->id()) {
      $update_count = user_bundle_update_users_type($this->getOriginalId(), $this->id());
      if ($update_count) {
        \Drupal::messenger()->addStatus(\Drupal::translation()->formatPlural($update_count,
          'Changed the account type of 1 user from %old-type to %type.',
          'Changed the account type of @count users from %old-type to %type.',
          [
            '%old-type' => $this->getOriginalId(),
            '%type' => $this->id(),
          ]));
      }
    }
    if ($update) {
      // Clear the cached field definitions as some settings affect the field
      // definitions.
      $this->entityManager()->clearCachedFieldDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the user type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

}
