<?php

namespace Drupal\invite\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\invite\InviteTypeInterface;

/**
 * Defines the Invite type entity.
 *
 * @ConfigEntityType(
 *   id = "invite_type",
 *   label = @Translation("Invite type"),
 *   handlers = {
 *     "list_builder" = "Drupal\invite\InviteTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\invite\Form\InviteTypeForm",
 *       "edit" = "Drupal\invite\Form\InviteTypeForm",
 *       "delete" = "Drupal\invite\Form\InviteTypeDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\invite\InviteTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "invite_type",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/invite_type/{invite_type}",
 *     "add-form" = "/admin/structure/invite_type/add",
 *     "edit-form" = "/admin/structure/invite_type/{invite_type}/edit",
 *     "delete-form" = "/admin/structure/invite_type/{invite_type}/delete",
 *     "collection" = "/admin/structure/invite_type",
 *   }
 * )
 */
class InviteType extends ConfigEntityBase implements InviteTypeInterface {
  /**
   * Invite type machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * Invite type human readable name.
   *
   * @var string
   */
  protected $label;

  /**
   * Invite type description.
   *
   * @var string
   */
  protected $description;

  /**
   * Invite type serialized data.
   *
   * @var string
   */
  protected $data;

  /**
   * Invite type status.
   *
   * @var int
   */
  protected $status;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('label', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('id', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    $this->set('data', $data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
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
  public function delete() {
    // Remove invite_sender records.
    InviteSender::load($this->get('id'))->delete();
    // Reload blocks.
    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
    parent::delete();
  }

}
