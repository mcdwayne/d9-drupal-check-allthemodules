<?php

namespace Drupal\commerce_iats;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class CommerceIats.
 */
class CommerceIats {

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * CommerceIats constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Gets a vault ID for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user account, optional. Defaults to the current user.
   *
   * @return string
   *   A vault ID for the user.
   */
  public function getUserVaultId(AccountInterface $account = NULL) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }
    return 'drupal:' . $this->getVaultIdPrefix() . ':' . $account->id();
  }

  /**
   * Gets the site's vault ID prefix.
   *
   * @return string
   *   The vault prefix.
   */
  public function getVaultIdPrefix() {
    return $this->configFactory
      ->get('commerce_iats.settings')
      ->get('vault_id_prefix');
  }

  /**
   * Sets a vault ID prefix for the site.
   *
   * @param string|null $prefix
   *   A prefix to use for vault IDs, or NULL to create a random prefix.
   */
  public function setVaultIdPrefix($prefix = NULL) {
    if ($this->getVaultIdPrefix()) {
      return;
    }

    if (!$prefix) {
      $prefix = user_password();
    }

    $this->configFactory
      ->getEditable('commerce_iats.settings')
      ->set('vault_id_prefix', $prefix)
      ->save();
  }

}
