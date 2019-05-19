<?php

namespace Drupal\user_lock\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\user_lock\UserLockConfigEntityInterface;

/**
 * Defines the User lock entity.
 *
 * @ConfigEntityType(
 *   id = "user_lock_config_entity",
 *   label = @Translation("User lock"),
 *   handlers = {
 *     "list_builder" = "Drupal\user_lock\UserLockConfigEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\user_lock\Form\UserLockConfigEntityForm",
 *       "edit" = "Drupal\user_lock\Form\UserLockConfigEntityForm",
 *       "delete" = "Drupal\user_lock\Form\UserLockConfigEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\user_lock\UserLockConfigEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "user_lock_config_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "user" = "user",
 *     "lock_period_from" = "lock_period_from",
 *     "lock_period_to" = "lock_period_to",
 *     "redirect_url" = "redirect_url",
 *     "lock_message" = "lock_message",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/user_lock_config_entity/{user_lock_config_entity}",
 *     "add-form" = "/admin/structure/user_lock_config_entity/add",
 *     "edit-form" = "/admin/structure/user_lock_config_entity/{user_lock_config_entity}/edit",
 *     "delete-form" = "/admin/structure/user_lock_config_entity/{user_lock_config_entity}/delete",
 *     "collection" = "/admin/structure/user_lock_config_entity"
 *   }
 * )
 */
class UserLockConfigEntity extends ConfigEntityBase implements UserLockConfigEntityInterface {
  /**
   * The User lock ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The User lock label.
   *
   * @var string
   */
  protected $label;

  /**
   * The User lock user.
   *
   * @var string
   */
  protected $user;

  /**
   * The User lock period from.
   *
   * @var string
   */
  protected $lock_period_from;

  /**
   * The User lock period to.
   *
   * @var string
   */
  protected $lock_period_to;

  /**
   * The User lock redirect URL.
   *
   * @var string
   */
  protected $redirect_url;

  /**
   * The User lock Message.
   *
   * @var string
   */
  protected $lock_message;

  /**
   * {@inheritdoc}
   */
  public function get_user() {
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function get_lock_period_from() {
    return $this->lock_period_from;
  }

  /**
   * {@inheritdoc}
   */
  public function setLockFrom($lock_period_from) {
    $this->set('lock_period_from', $lock_period_from);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser($user) {
    $this->set('user', $user);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLockTo($lock_period_to) {
    $this->set('lock_period_to', $lock_period_to);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function get_lock_period_to() {
    return $this->lock_period_to;
  }

  /**
   * {@inheritdoc}
   */
  public function get_redirect_url() {
    return $this->redirect_url;
  }

  /**
   * {@inheritdoc}
   */
  public function get_lock_message() {
    return $this->lock_message;
  }
}
