<?php

namespace Drupal\bookkeeping\Form;

use Drupal\bookkeeping\Entity\Account;

/**
 * Trait for bookkeeping settings form where accounts are referenced.
 */
trait AccountSettingsTrait {

  /**
   * The entity type handler.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The account options, outer keyed by type.
   *
   * @var array
   */
  private $accountOptions;

  /**
   * Get the account options.
   *
   * @param string|null $type
   *   The account type, or NULL to receive them all.
   *
   * @return array
   *   An array of accounts suitable for #options.
   */
  protected function getAccountsOptions(string $type = NULL) {
    if (!isset($this->accountOptions)) {
      /** @var \Drupal\bookkeeping\Entity\AccountInterface[] $accounts */
      $accounts = $this->entityTypeManager
        ->getStorage('bookkeeping_account')
        ->loadMultiple();
      uasort($accounts, [Account::class, 'sort']);

      foreach ($accounts as $account) {
        $this->accountOptions[$account->getType()][$account->id()] = $account->label();
      }
    }

    // Return the required subset.
    if ($type) {
      return $this->accountOptions[$type] ?? [];
    }
    return $this->accountOptions;
  }

}
