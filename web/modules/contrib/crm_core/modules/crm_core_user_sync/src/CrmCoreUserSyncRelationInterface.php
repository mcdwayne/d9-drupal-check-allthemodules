<?php

namespace Drupal\crm_core_user_sync;

use Drupal\crm_core_contact\IndividualInterface;
use Drupal\user\UserInterface;

/**
 * CrmCoreUserSyncRelation service.
 */
interface CrmCoreUserSyncRelationInterface {

  /**
   * Retrieves the individual ID from the user ID.
   *
   * @return int|null
   *   Individual ID, if relation exists.
   */
  public function getIndividualIdFromUserId($user_id);

  /**
   * Retrieves the user ID from the individual ID.
   *
   * @return int|null
   *   User ID, if relation exists.
   */
  public function getUserIdFromIndividualId($individual_id);

  /**
   * Retrieves the relation ID from the user ID.
   *
   * @return int|null
   *   Relation ID, if exists.
   */
  public function getRelationIdFromUserId($user_id);

  /**
   * Retrieves the relation ID from the individual ID.
   *
   * @return int|null
   *   Relation ID, if exists.
   */
  public function getRelationIdFromIndividualId($individual_id);

  /**
   * Synchronizes user and contact.
   *
   * @param \Drupal\user\UserInterface $account
   *   Account to be synchronized. Programmatically created accounts can
   *   override default behavior by setting
   *   $account->crm_core_no_auto_sync = TRUE.
   * @param \Drupal\crm_core_contact\IndividualInterface $contact
   *   Contact to be associated with $account.
   *
   * @return \Drupal\crm_core_contact\ContactInterface
   *   A contact object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function relate(UserInterface $account, IndividualInterface $contact = NULL);

}
