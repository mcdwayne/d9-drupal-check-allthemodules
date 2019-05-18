<?php

namespace CleverReach\BusinessLogic\Interfaces;

/**
 *
 */
interface Recipients {
  const CLASS_NAME = __CLASS__;

  /**
   * Gets all tags as a collection.
   *
   * @return \CleverReach\BusinessLogic\Entity\TagCollection
   */
  public function getAllTags();

  /**
   * Gets all recipients for passed batch id with tags formatted in the proper way.
   *
   * @param array $batchRecipientIds
   * @param bool $includeOrders
   *
   * @return array of CleverReach\BusinessLogic\Entity\Recipient objects based on passed ids.
   *   - If includeOrders flag is set to true orders should also be returned with other recipient data. SPECIAL
   *   - ATTENTION should be pointed towards tags. They should be in instance specific formatted way. If instance has
   *   - groups G1 and G3 array of strings for tags should be: array(G-G1, G-G2,)
   *
   * @throws RecipientsGetException
   */
  public function getRecipientsWithTags(array $batchRecipientIds, $includeOrders);

  /**
   * Gets all recipients IDs from source system.
   *
   * @return array of strings
   *
   * @throws RecipientsGetException
   */
  public function getAllRecipientsIds();

  /**
   * Informs service about completed synchronization of provided recipients (IDs).
   *
   * @param array $recipientIds
   */
  public function recipientSyncCompleted(array $recipientIds);

}
