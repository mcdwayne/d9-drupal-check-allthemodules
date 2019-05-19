<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * Xero User type.
 *
 * @DataType(
 *   id = "xero_user",
 *   label = @Translation("Xero User"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\UserDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 *
 * @todo user roles?
 */
class User extends XeroTypeBase {

  static public $guid_name = 'UserID';
  static public $xero_name = 'User';
  static public $plural_name = 'Users';
  static public $label = 'EmailAddress';

  /**
   * Is the user account a subscriber?
   *
   * @return boolean
   *   Return TRUE if the user is a subscriber.
   */
  public function isSubscriber() {
    return $this->get('IsSubscriber')->getValue();
  }

}
