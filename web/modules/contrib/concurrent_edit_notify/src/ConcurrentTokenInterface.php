<?php

namespace Drupal\concurrent_edit_notify;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines a common interface for all Tokens.
 *
 * @ingroup entity_api
 */
interface ConcurrentTokenInterface {

  /**
   * Saves token.
   *
   * When saving existing entities, the entity is assumed to be complete,
   * partial updates of entities are not supported.
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function save(array $token);

  /**
   * Deletes an entity permanently.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function delete($nid, $langcode);

  /**
   * Gets the tokens.
   *
   * @return int|string|null
   *   The original ID, or NULL if no ID was set or for entity types that do not
   *   support renames.
   */
  public function load($nid, $langcode);

  /**
   * Gets the first token.
   *
   * @return int|string|null
   *   The original ID, or NULL if no ID was set or for entity types that do not
   *   support renames.
   */
  public function loadFirst($nid, $langcode);

  /**
   * Check if warning message is displayed.
   *
   * @return int|string|null
   *   The original ID, or NULL if no ID was set or for entity types that do not
   *   support renames.
   */
  public function isDisplayed($nid, $langcode, $uid);

  /**
   * Set TRUE if warning message is displayed.
   *
   * @return int|string|null
   *   The original ID, or NULL if no ID was set or for entity types that do not
   *   support renames.
   */
  public function setDisplayed($nid, $langcode, $uid);

  /**
   * Set FALSE if node page is reloaded.
   *
   * @return int|string|null
   *   The original ID, or NULL if no ID was set or for entity types that do not
   *   support renames.
   */
  public function resetDisplayed($nid, $langcode, $uid);

  /**
   * Check if token exists.
   *
   * @return int|string|null
   *   The original ID, or NULL if no ID was set or for entity types that do not
   *   support renames.
   */
  public function check(array $token);

  /**
   * Check if account is logged in.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object.
   *
   * @return bool
   *   Return TRUE if account is logged in, esle FALSE.
   */
  public function isAccountLoggedIn(AccountInterface $account);

}
