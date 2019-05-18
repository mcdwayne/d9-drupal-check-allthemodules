<?php

namespace Drupal\samlauth\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a samlauth user sync event for event listeners.
 */
class SamlauthUserSyncEvent extends Event {

  /**
   * The Drupal user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The SAML attributes received from the IDP.
   *
   * Single values are typically represented as one-element arrays.
   *
   * @var array
   */
  protected $attributes;

  /**
   * A flag indicating that the account was changed.
   *
   * @var bool
   */
  protected $accountChanged;

  /**
   * Constructs a samlauth user sync event object.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   * @param array $attributes
   *   The SAML attributes received from the IDP.
   */
  public function __construct(UserInterface $account, array $attributes) {
    $this->account = $account;
    $this->attributes = $attributes;
  }

  /**
   * Gets the Drupal user entity.
   *
   * @return \Drupal\user\UserInterface $account
   *   The Drupal user account.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Sets the altered Drupal user entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   */
  public function setAccount(UserInterface $account) {
    $this->account = $account;
  }

  /**
   * Gets the SAML attributes.
   *
   * @return array
   *   The SAML attributes received from the IDP.
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * Sets the SAML attributes.
   *
   * Event handlers typically will use only the getter, to inspect attributes
   * that are present in the SAML message. This setter provides a way to
   * override those values in edge cases but is not meant to be used often. It
   * provides no nice DX; the caller needs to make sure that all attributes are
   * set at once (probably by first using getAttributes() and changing the
   * appropriate values). The caller also needs to make sure that values are
   * structured as other event subscribers expect them (which is likely to be
   * single-value arrays).
   *
   * @param array $attributes
   *   An array containing SAML attributes.
   */
  public function setAttributes(array $attributes) {
    $this->attributes = $attributes;
  }

  /**
   * Marks the user account as changed.
   *
   * This is the way for event subscribers to make sure user account gets saved.
   * This method exists because subscribers must never save new accounts by
   * themselves. (Non-new accounts could be saved by the event subscribers but
   * just calling markAccountChanged() will keep the account from being saved
   * multiple times by multiple subscribers.)
   */
  public function markAccountChanged() {
    $this->accountChanged = TRUE;
  }

  /**
   * Checks whether the user account was marked as changed.
   *
   * This is typically done afterwards by the code that dispatches this.
   *
   * @return bool
   *   TRUE if the user account was marked as changed.
   */
  public function isAccountChanged() {
    return $this->accountChanged;
  }

}
