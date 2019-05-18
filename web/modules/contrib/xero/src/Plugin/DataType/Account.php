<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * Xero Account type.
 *
 * @DataType(
 *   id = "xero_account",
 *   label = @Translation("Xero Account"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\AccountDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Account extends XeroTypeBase {

  static public $guid_name = 'AccountID';
  static public $xero_name = 'Account';
  static public $plural_name = 'Accounts';
  static public $label = 'AccountCode';

  /**
   * See if an account can be used as a revenue account.
   *
   * @return boolean
   *   Return TRUE if the account is revenue-based.
   *
   * @throws \Exception
   */
  public function isRevenue() {
    $class = $this->get('Class')->getValue();
    if ($class && $class == 'REVENUE') {
      return TRUE;
    }
    elseif (!$class) {
      throw new \Exception('Invalid use of isRevenue method');
    }

    return FALSE;
  }

  /**
   * See if an account is a bank account.
   *
   * @retun boolean
   *   Return TRUE if the account is a bank account.
   */
  public function isBankAccount() {
    $type = $this->get('Type')->getValue();

    return $type == 'BANK';
  }

}
