<?php

namespace Drupal\licensing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Class LicensingService.
 *
 * @package Drupal\licensing
 */
class LicensingService {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface*/
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * @param int $uid
   * @param int $entity_id
   * @param int $status
   */
  public function createOrUpdateLicense($uid, $entity_id, $status) {
    /** @var \Drupal\licensing\Entity\License $license */
    if ($license = $this->loadExistingLicense($uid, $entity_id)) {
      $license->status = $status;
      $license->save();
      $this->logger->info("Updated license @entity_id to status @status.", ['@entity_id' => $entity_id, '@status' => $status]);
    }
    else {
      $license = $this->createLicense($uid, $entity_id);
      $this->logger->info("Created license @entity_id with status @status.", ['@entity_id' => $license->id(), '@status' => $status]);
    }
  }

  /**
   * @param $uid
   * @param $entity_id
   *
   * @return null|\Drupal\licensing\Entity\License
   */
  public function loadExistingLicense($uid, $entity_id) {
    $licenses = $this->entityTypeManager
      ->getStorage('license')
      ->loadByProperties([
        'user_id' => $uid,
        'licensed_entity' => $entity_id,
      ]);
    if (!empty($licenses)) {
      /** @var \Drupal\licensing\Entity\License $first_license */
      $first_license = reset($licenses);

      return $first_license;
    }

    return NULL;
  }

  /**
   * @param int $uid
   * @param int $entity_id
   * @param int $status
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function createLicense($uid, $entity_id, $status = LICENSE_ACTIVE) {
    $license = $this->entityTypeManager->getStorage('license')->create([
      'type' => 'default',
      'user_id' => $uid,
      'licensed_entity' => $entity_id,
      'status' => $status,
    ]);
    $license->save();

    return $license;
  }

}
