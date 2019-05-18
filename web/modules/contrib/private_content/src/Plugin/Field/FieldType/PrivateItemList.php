<?php

namespace Drupal\private_content\Plugin\Field\FieldType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;

/**
 * List class for PrivateItem.
 */
class PrivateItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    if ($operation == 'edit') {
      return AccessResult::allowedIfHasPermission($account, 'mark content as private');
    }

    return parent::defaultAccess($operation, $account);
  }

  /**
   * Return whether node is private.
   *
   * @return boolean True if private.
   */
  public function isPrivate() {
    return (count($this->list)) ? $this->value : $this->getDefault();
  }

  /**
   * Return default value of private field, based on the node's content type.
   *
   * @return boolean
   *   Default value.
   */
  public function getDefault() {
    return $this->checkTypeSetting(PRIVATE_ALWAYS, PRIVATE_AUTOMATIC);
  }

  /**
   * Return whether private field is locked (not-writeable), based on the node's
   * content type.
   *
   * @return boolean
   *   True if locked.
   */
  public function isLocked() {
    return $this->checkTypeSetting(PRIVATE_ALWAYS, PRIVATE_DISABLED);
  }

  /**
   * Get the node's content type setting and check against the listed values.
   *
   * @param boolean $value1
   *   First value to check against
   *
   * @param boolean $value2
   *   Second value to check against
   *
   * @return boolean
   *   True if type setting matches one of the values.
   */
  protected function checkTypeSetting($value1, $value2) {
    /** @var \Drupal\node\NodeTypeInterface $type */
    $type = $this->getEntity()->type->entity;
    $type_setting = $type->getThirdPartySetting('private_content', 'private', PRIVATE_ALLOWED);
    return ($type_setting == $value1) || ($type_setting == $value2);
  }

}
