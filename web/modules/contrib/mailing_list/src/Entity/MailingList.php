<?php

namespace Drupal\mailing_list\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\mailing_list\MailingListInterface;

/**
 * Defines the mailing list configuration entity.
 *
 * @ConfigEntityType(
 *   id = "mailing_list",
 *   label = @Translation("Mailing list"),
 *   label_singular = @Translation("Mailing list"),
 *   label_plural = @Translation("Mailing lists"),
 *   handlers = {
 *     "list_builder" = "Drupal\mailing_list\MailingListListBuilder",
 *     "access" = "Drupal\mailing_list\MailingListAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\mailing_list\Form\MailingListForm",
 *       "edit" = "Drupal\mailing_list\Form\MailingListForm",
 *       "delete" = "Drupal\mailing_list\Form\MailingListDeleteConfirmForm",
 *     },
 *   },
 *   admin_permission = "administer mailing lists",
 *   config_prefix = "mailing_list",
 *   bundle_of = "mailing_list_subscription",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/mailing_lists/add",
 *     "edit-form" = "/admin/structure/mailing_lists/{mailing_list}/edit",
 *     "delete-form" = "/admin/structure/emailing_lists/{mailing_list}/delete",
 *     "collection" = "/admin/structure/mailing_lists",
 *     "auto-label" = "/admin/structure/mailing_lists/{mailing_list}/auto-label",
 *     "export" = "/admin/structure/mailing_lists/{mailing_list}/export",
 *     "import" = "/admin/structure/mailing_lists/{mailing_list}/import",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "help",
 *     "max_per_user",
 *     "max_per_email",
 *     "inactive_subscriptions_liftime",
 *     "subscription_message",
 *     "cancellation_message",
 *     "cross_access",
 *     "form_destination",
 *   },
 * )
 */
class MailingList extends ConfigEntityBundleBase implements MailingListInterface {

  /**
   * A brief description of this mailing list.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information when creating a new subscription on this list.
   *
   * @var string
   */
  protected $help;

  /**
   * Subscription limit per user.
   *
   * @var int
   */
  protected $max_per_user;

  /**
   * Subscription limit per email address.
   *
   * @var int
   */
  protected $max_per_email;

  /**
   * Inactive subscription lifetime.
   *
   * @var int
   */
  protected $inactive_subscriptions_liftime;

  /**
   * After subscription message.
   *
   * @var string
   */
  protected $subscription_message;

  /**
   * On subscription cancellation message.
   *
   * @var string
   */
  protected $cancellation_message;

  /**
   * Subscription form destination.
   *
   * @var string
   */
  protected $form_destination;

  /**
   * Subscription cross access allowance.
   *
   * @var unknown
   */
  protected $cross_access;

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help;
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
  public function getLimitByUser() {
    return $this->max_per_user;
  }

  /**
   * {@inheritdoc}
   */
  public function setLimitByUser($limit) {
    $this->max_per_user = $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimitByEmail() {
    return $this->max_per_email;
  }

  /**
   * {@inheritdoc}
   */
  public function setLimitByEmail($limit) {
    $this->max_per_email = $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function getInactiveLifetime() {
    return $this->inactive_subscriptions_liftime;
  }

  /**
   * {@inheritdoc}
   */
  public function setInactiveLifetime($time) {
    $this->inactive_subscriptions_liftime = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getOnSubscriptionMessage() {
    return $this->subscription_message;
  }

  /**
   * {@inheritdoc}
   */
  public function setOnSubscriptionMessage($message) {
    $this->subscription_message = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getOnCancellationMessage() {
    return $this->cancellation_message;
  }

  /**
   * {@inheritdoc}
   */
  public function setOnCancellationMessage($message) {
    $this->cancellation_message = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDestination() {
    return $this->form_destination;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormDestination($destination) {
    $this->form_destination = $destination;
  }

  /**
   * {@inheritdoc}
   */
  public function isCrossAccessAllowed() {
    return $this->cross_access;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update) {
      // Update subscriptions mailing list.
      if ($this->getOriginalId() != $this->id()) {
        $update_count = $this->entityTypeManager()->getStorage('subscription')->updateType($this->getOriginalId(), $this->id());
        if ($update_count) {
          drupal_set_message(\Drupal::translation()->formatPlural($update_count,
            'Changed the mailing list of 1 subscription from %old-type to %type.',
            'Changed the mailing list of @count subscriptions from %old-type to %type.',
            [
              '%old-type' => $this->getOriginalId(),
              '%type' => $this->id(),
            ]));
        }
      }
    }
    else {
      // Create block form display mode.
      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $block_display_mode */
      $block_display_mode = $this->entityTypeManager()->getStorage('entity_form_display')->create([
        'id' => 'mailing_list_subscription.' . $this->id() . '.block',
        'targetEntityType' => 'mailing_list_subscription',
        'bundle' => $this->id(),
        'mode' => 'block',
        'status' => TRUE,
      ]);

      // Disable admin components.
      $block_display_mode->removeComponent('uid')
        ->removeComponent('created')
        ->removeComponent('status')
        ->removeComponent('langcode')
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Remove permissions of the mailing list.
    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
    foreach ($entities as $entity) {
      $list_id = $entity->id();
      foreach ($roles as $role) {
        $save_role = FALSE;
        /* @var \Drupal\user\RoleInterface $role */
        foreach ([
          "subscribe to $list_id mailing list",
          "access inactive $list_id mailing list subscriptions",
          "view any $list_id mailing list subscriptions",
          "update any $list_id mailing list subscriptions",
          "delete any $list_id mailing list subscriptions",
        ] as $permission) {
          if ($role->hasPermission($permission)) {
            $save_role = TRUE;
            $role->revokePermission($permission);
          }
        }

        if ($save_role) {
          $role->save();
        }
      }

      // Remove subscription blocks.
      foreach (\Drupal::entityTypeManager()->getStorage('block')->loadMultiple(
        \Drupal::entityQuery('block')
          ->condition('plugin', 'mailing_list_subscription_block')
          ->condition('settings.list', $entity->id())
          ->execute()) as $block) {
        $block->delete();
      }
    }

    // Clear the cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

}
