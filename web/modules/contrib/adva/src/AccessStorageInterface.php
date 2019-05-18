<?php

namespace Drupal\adva;

use Drupal\adva\Plugin\adva\OverridingAccessConsumerInterface;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a storage handler for the advanced access grants system.
 *
 * This service stores access requirements in the database and is used to check
 * entity access by querying these records during access checks by performed by
 * AdvancedAccessEntityAccessControlHandler instances.
 *
 * @ingroup adva
 */
interface AccessStorageInterface {

  /**
   * Check access for a given entity and operation by a user.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity Instance.
   * @param string $operation
   *   Access operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to check privs for.
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account);

  /**
   * Check if a given user has the permission to bypass access checks.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to check privs for.
   *
   * @return bool
   *   True is user has the global bypass permission.
   */
  public function hasGlobalBypassPermission(AccountInterface $account);

  /**
   * Check if a given user has the permission to bypass access checks on a type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to check privs for.
   * @param string $entity_type_id
   *   Entity Type to check global piv for.
   *
   * @return bool
   *   True is user has the global bypass permission for the type.
   */
  public function hasEntityTypeBypassPermission(AccountInterface $account, $entity_type_id);

  /**
   * Clear access records for a given entity type.
   *
   * Deletes all access records and re adds the default record.
   *
   * @param string $entity_type_id
   *   Entity Type to clear grants for.
   */
  public function clearRecords($entity_type_id);

  /**
   * Empty records storage table in database.
   *
   * @param string $entity_type_id
   *   Entity Type to delete grants for.
   */
  public function deleteRecords($entity_type_id);

  /**
   * Generate and insert default grant into database.
   *
   * @param string $entity_type_id
   *   Entity Type to create global grant for.
   */
  public function saveDefaultGrant($entity_type_id);

  /**
   * Build default entry conditional for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity Instance.
   *
   * @return Drupal\Core\Database\Query\Condition
   *   Default access condition.
   */
  public function defaultCondition(EntityInterface $entity);

  /**
   * Build access condition for query.
   *
   * @param array $grants
   *   List of grants where 'realm' => array(...$gids).
   *
   * @return Drupal\Core\Database\Query\Condition
   *   Access Conditional group.
   */
  public function buildAccessCondition(array $grants);

  /**
   * Build grant condition set for grants.
   *
   * @param array $grants
   *   List of grants where 'realm' => array(...$gids).
   *
   * @return Drupal\Core\Database\Query\Condition
   *   Grant access condition.
   */
  public function buildGrantsCondition(array $grants);

  /**
   * Reload all access records for a given consumer.
   *
   * @param \Drupal\adva\Plugin\adva\OverridingAccessConsumerInterface $consumer
   *   Access Consumer instance.
   * @param bool $batch_mode
   *   If true, create and execute rebuild as a batch job.
   */
  public function rebuild(OverridingAccessConsumerInterface $consumer, $batch_mode = FALSE);

  /**
   * Save records for an entity.
   *
   * Retrieves an entites grants, and saves them with `saveRecords`.
   *
   * @param \Drupal\adva\Plugin\adva\OverridingAccessConsumerInterface $consumer
   *   Access Consumer instance.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity Instance.
   */
  public function updateRecordsFor(OverridingAccessConsumerInterface $consumer, EntityInterface $entity);

  /**
   * Execute query to save records for a entity to the database.
   *
   * @param \Drupal\adva\Plugin\adva\OverridingAccessConsumerInterface $consumer
   *   Access Consumer instance.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity Instance.
   * @param array $grants
   *   Array of grant record entries.
   * @param bool $delete
   *   Delete existing records from the database before saving.
   */
  public function saveRecords(OverridingAccessConsumerInterface $consumer, EntityInterface $entity, array $grants, $delete = TRUE);

  /**
   * Calculate access records for an entity.
   *
   * @param \Drupal\adva\Plugin\adva\OverridingAccessConsumerInterface $consumer
   *   Access Consumer instance.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity Instance.
   *
   * @retrun array
   *   Array of grant record entries.
   */
  public function getRecordsFor(OverridingAccessConsumerInterface $consumer, EntityInterface $entity);

  /**
   * Get operation grants for a user.
   *
   * @param \Drupal\adva\Plugin\adva\OverridingAccessConsumerInterface $consumer
   *   Access Consumer instance.
   * @param string $operation
   *   Access operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to check privs for.
   *
   * @return array
   *   List of grants where 'realm' => array(...$gids).
   */
  public function getUserGrants(OverridingAccessConsumerInterface $consumer, $operation, AccountInterface $account);

  /**
   * Count the number of rows in the table, for an entity type.
   *
   * @param string $entity_type_id
   *   Entity Type id.
   *
   * @return int
   *   Count of records for the given type.
   */
  public function count($entity_type_id);

  /**
   * Delete access records for an entity.
   *
   * When when an entity is deleted or access is being rebuild, we need to clean
   * up the old records.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity Instance.
   */
  public function deleteRecordsFor(EntityInterface $entity);

}
