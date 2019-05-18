<?php

/**
 * @file
 * Contains \Drupal\cronpub\CronpubExecutionService.
 */

namespace Drupal\cronpub;

use Drupal\cronpub\Entity\CronpubEntity;

/**
 * Class CronpubExecutionService.
 *
 * @package Drupal\cronpub
 */
class CronpubExecutionService {

  /**
   * Contains the active CronpubEntity instances.
   *
   * @var array
   */
  private $cronpubEntities;

  /**
   * Contains the active CronpubEntity instances.
   *
   * @var array
   */
  private $entityTypeManager;

  /**
   * The reference timestamp the execution is working on.
   *
   * @var \DateTime
   */
  private $date;

  /**
   * The reference timestamp the execution is working on.
   *
   * @var integer
   */
  private $timestamp;

  /**
   * @var \Drupal\cronpub\Plugin\Cronpub\CronpubActionManager
   */
  private $plugin_manager;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->cronpubEntities = CronpubEntity::loadMultiple();
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $timezone = new \DateTimeZone(DATETIME_STORAGE_TIMEZONE);
    $this->date = new \DateTime('now', $timezone);
    $this->timestamp = $this->date->getTimestamp();
    $this->plugin_manager = \Drupal::service('plugin.manager.cronpub');
  }

  /**
   * Loads the content entity from a config entity.
   *
   * @param \Drupal\cronpub\Entity\CronpubEntity $cronpub_entity
   *    The config entity with included parameters to load content entity.
   *
   * @return \Drupal\core\Entity\ContentEntityBase
   *    The content entity.
   */
  public function getContentEntity(CronpubEntity $cronpub_entity) {
    $type = $cronpub_entity->getTargetType();
    $storage = $this->entityTypeManager->getStorage($type);
    $id = $cronpub_entity->getTargetId();
    return $storage->load($id);
  }

  /**
   * Walk over all Entities an do the publication.
   *
   * @return bool
   *   Any errors occurred during execution.
   */
  public function execAllPublishing() {
    foreach ($this->cronpubEntities as $entity) {
      $this->execPublishing($entity);
    }
  }

  /**
   * Gets the content entity and does the publication.
   *
   * @return bool
   *   Any errors occurred during execution.
   */

  /**
   * @param CronpubEntity $cronpub_entity
   */
  public function execPublishing(CronpubEntity $cronpub_entity) {
    if ($cronpub_entity->selfTest()) {
      $content_entity = $this->getContentEntity($cronpub_entity);
      if ($content_entity) {
        $dates = $cronpub_entity->getChronology();
        $plugin_id = $cronpub_entity->getPlugin();
        $plugin = $this->plugin_manager->createInstance($plugin_id);
        foreach ($dates as $time => $data) {
          // Check chronology if current job is to execute.
          if (!in_array($data['state'], ['pending', 'repeat']) || ($time > $this->timestamp)) {
            continue;
          }
          switch ($data['job']) {
            case 'start':
              $plugin->startAction($content_entity);
              // Even if we changed nothing the job has done.
              $dates[$time]['state'] = $this->timestamp;
              break;

            case 'end':
              $plugin->endAction($content_entity);
              // Even if we changed nothing the job has done.
              $dates[$time]['state'] = $this->timestamp;
              break;
          }
        }
        $cronpub_entity->setChronology($dates);
        $cronpub_entity->save();
      }
      else {
        // If no content entity exists for this, it is use less.
        $cronpub_entity->delete();
      }
    }
  }


}
