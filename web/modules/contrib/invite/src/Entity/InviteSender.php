<?php

namespace Drupal\invite\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\invite\InviteTypeInterface;

/**
 * Defines the Invite sender entity.
 *
 * @ConfigEntityType(
 *   id = "invite_sender",
 *   label = @Translation("Invite Sender"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *   },
 *   config_prefix = "invite_sender",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "type" = "type",
 *     "name" = "name",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class InviteSender extends ConfigEntityBase implements InviteTypeInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('name', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

}
