<?php

namespace Drupal\media_riddle_marketplace;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\riddle_marketplace\RiddleFeedServiceInterface;

/**
 * Class RiddleFeedService.
 *
 * @package Drupal\riddle_marketplace
 */
class RiddleMediaService implements RiddleMediaServiceInterface {

  /**
   * The riddle feed service.
   *
   * @var \Drupal\riddle_marketplace\RiddleFeedServiceInterface
   */
  protected $feedService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Riddle Media Service.
   *
   * Constructor.
   *
   * @param \Drupal\riddle_marketplace\RiddleFeedServiceInterface $feedService
   *   Riddle Feed service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(RiddleFeedServiceInterface $feedService, EntityTypeManagerInterface $entityTypeManager, Connection $database) {
    $this->feedService = $feedService;
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function createMediaEntities() {

    foreach ($this->getNewRiddles() as $type => $riddles) {
      /** @var \Drupal\media\MediaTypeInterface $type */
      $type = $this->entityTypeManager->getStorage('media_type')
        ->load($type);
      $sourceField = $type->get('source_configuration')['source_field'];

      foreach ($riddles as $riddle) {
        $this->entityTypeManager->getStorage('media')->create([
          'bundle' => $type->id(),
          $sourceField => $riddle,
        ])->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNewRiddles() {

    $feed = $this->feedService->getFeed();
    $riddle_feed_ids = array_column($feed, 'id');

    if (empty($riddle_feed_ids)) {
      return [];
    }

    /** @var \Drupal\media\MediaTypeInterface[] $riddleBundles */
    $riddleBundles = $this->entityTypeManager->getStorage('media_type')
      ->loadByProperties([
        'source' => 'riddle_marketplace',
      ]);

    $newRiddles = [];
    foreach ($riddleBundles as $type) {

      $sourceField = $type->get('source_configuration')['source_field'];
      $existing_riddle_id = $this->database->select("media__$sourceField", 'n')
        ->condition("n.${sourceField}_value", $riddle_feed_ids, 'IN')
        ->fields('n', ["${sourceField}_value"])
        ->execute()
        ->fetchCol();

      // Sort oldest riddles to the top, so they will be created first.
      $new_riddles = array_diff($riddle_feed_ids, $existing_riddle_id);
      sort($new_riddles);

      $newRiddles[$type->id()] = $new_riddles;
    }

    return $newRiddles;
  }

}
