<?php

namespace Drupal\acquia_contenthub\EventSubscriber\EntityImport;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\EntityImportEvent;
use Drupal\user\UserData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ImportUserData.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\ExtraCdfFields
 */
class ImportUserData implements EventSubscriberInterface {

  /**
   * UserData service.
   *
   * @var \Drupal\user\UserData
   */
  protected $userData;

  protected const IMPORT_USER_DATA_METHOD = 'onImportUserData';

  /**
   * ImportUserDataExtraField constructor.
   *
   * @param \Drupal\user\UserData $user_data
   */
  public function __construct(UserData $user_data) {
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::ENTITY_IMPORT_NEW][] = self::IMPORT_USER_DATA_METHOD;
    $events[AcquiaContentHubEvents::ENTITY_IMPORT_UPDATE][] = self::IMPORT_USER_DATA_METHOD;

    return $events;
  }

  /**
   * Imports User Data.
   *
   * @param \Drupal\acquia_contenthub\Event\EntityImportEvent $event
   *   Event object.
   */
  public function onImportUserData(EntityImportEvent $event) {
    $entity = $event->getEntity();
    if ('user' !== $entity->getEntityTypeId()) {
      return;
    }

    $cdf = $event->getEntityData();
    $metadata = $cdf->getMetadata();

    if (!isset($metadata['user_data'])) {
      return;
    }

    $uid = $entity->id();

    // Delete all current User Data.
    $this->userData->delete(NULL, $uid);

    // Import actual User Data.
    foreach ($metadata['user_data'] as $module => $data) {
      foreach ($data as $key => $value) {
        $this->userData->set($module, $uid, $key, $value);
      }
    }
  }

}
