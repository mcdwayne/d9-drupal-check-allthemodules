<?php

namespace Drupal\community_tasks;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\Core\Extension\ModuleHandler;


/**
 * Migration modifications
 */
class MigrationSubscriber implements EventSubscriberInterface {

  /**
   * @var Drupal\Core\Extension\ModuleHandler
   */
  private $moduleHandler;


  function __construct(ModuleHandler $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      //Drupal\migrate\Event\MigrateEvents::PRE_ROW_SAVE
      'migrate.pre_row_save' => [['migratePreRowSave']]
    ];
  }


  /**
   * @param Drupal\migrate\Event\MigratePreRowSaveEvent $event
   */
  public function migratePreRowSave(MigratePreRowSaveEvent $event) {
    $migration = $event->getMigration();
    // Ensure the category->required setting is transferred.
    if ($migration->id() == 'd7_node:community_task') {
      $row = $event->getRow();
      if ($row->getSourceProperty('type') == 'community_task') {
        if ($row->getSourceProperty('uid') == 1) {
          $row->setDestinationProperty('ctask_state', 'open');
        }
        elseif($row->getSourceProperty('promote')) {
          $row->setDestinationProperty('ctask_state', 'completed');
          $row->setDestinationProperty('promote', 0);
        }
        else {
          $row->setDestinationProperty('ctask_state', [0 => ['value' => 'committed']]);
        }
      }
    }
  }

}
