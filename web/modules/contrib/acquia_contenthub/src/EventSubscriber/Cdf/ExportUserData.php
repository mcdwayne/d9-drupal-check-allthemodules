<?php

namespace Drupal\acquia_contenthub\EventSubscriber\Cdf;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\CreateCdfEntityEvent;
use Drupal\Core\Database\Connection;
use Drupal\user\UserData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ExportUserData.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\ExtraCdfFields
 */
class ExportUserData implements EventSubscriberInterface {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * UserData service.
   *
   * @var \Drupal\user\UserData
   */
  protected $userData;

  /**
   * UsersData constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\user\UserData $user_data
   *   User Data service.
   */
  public function __construct(Connection $database, UserData $user_data) {
    $this->database = $database;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::CREATE_CDF_OBJECT][] = ['onCreateCdf'];

    return $events;
  }

  /**
   * Serializes User Data.
   *
   * @param \Drupal\acquia_contenthub\Event\CreateCdfEntityEvent $event
   *   Event object.
   */
  public function onCreateCdf(CreateCdfEntityEvent $event) {
    $entity = $event->getEntity();
    if ('user' !== $entity->getEntityTypeId()) {
      return;
    }

    $cdf = $event->getCdf($entity->uuid());
    $metadata = $cdf->getMetadata();
    $metadata['user_data'] = [];

    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->database->select('users_data', 'ud')
      ->fields('ud', ['module'])
      ->condition('uid', $entity->id());
    $modules = $query->execute()->fetchCol();
    if (!$modules) {
      $cdf->setMetadata($metadata);
      return;
    }

    foreach ($modules as $module) {
      $metadata['user_data'][$module] = $this->userData->get($module, $entity->id());
    }

    $cdf->setMetadata($metadata);
  }

}
