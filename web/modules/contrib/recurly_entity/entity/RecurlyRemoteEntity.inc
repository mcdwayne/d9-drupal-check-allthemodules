<?php

/**
 * @interface
 * Define all methods that should be implemented by any Recurly entity.
 */
interface RecurlyRemoteEntity {

  /**
   * Load an entity from Recurly using it's unique identifier.
   *
   * @param array $ids
   *   An array of Recurly IDs to load, such as the plan_code for a Subscription
   *   Plan or the account_code for an Account.
   *
   * @return array
   *   An array of Recurly entities.
   */
  public function loadFromRecurlyMultiple(array $ids);

  /**
   * Load an entity from Recurly using it's unique identifier.
   *
   * @param string $id
   *   The ID of the entity to load, such as the plan_code for a Subscription
   *   Plan or the account_code for an Account.
   *
   * @return RecurlyEntity
   *   The Recurly entity that was loaded.
   */
  public function loadFromRecurly($id);

  /**
   * Map a Recurly code to an entity ID.
   *
   * @param string $code
   *   The unique Recurly code, such as an account code or plan code.
   *
   * @return int
   *   The entity ID of the recurly object, if it exists.
   */
  public function entityId($code);

  /**
   * Map multiple Recurly codes to their entity ID.
   *
   * @param array $codes
   *   An array of Recurly codes.
   *
   * @return array
   *   An array of entity IDs, keyed by their code.
   */
  public function entityIdMultiple(array $codes);

  /**
   * Map an Entity ID to a Recurly code.
   *
   * @param int $entity_id
   *   The entity ID of the recurly object.
   *
   * @return string
   *   The unique Recurly code.
   */
  public function recurlyCode($entity_id);

  /**
   * Map an array of Entity IDs to Recurly codes.
   *
   * @param array $entity_ids
   *   The array of entity IDs to map.
   *
   * @return array
   *   An array of recurly codes.
   */
  public function recurlyCodeMultiple(array $entity_ids);

  /**
   * Return the Recurly class that should be used to interact with Recurly.
   *
   * @return Recurly_Resource
   *   The Recurly_Resource class, such as Recurly_Subscription.
   */
  public function resourceClass();
}
