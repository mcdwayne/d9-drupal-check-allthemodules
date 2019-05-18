<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;
use Drupal\file\Entity\File;

/**
 * Class EmbeddedImagesCollector.
 *
 * @package Drupal\depcalc\EventSubscriber\DependencyCollector
 */
class EmbeddedImagesCollector extends BaseDependencyCollector {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHanlder;

  /**
   * EmbeddedImagesCollector constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->moduleHanlder = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DependencyCalculatorEvents::CALCULATE_DEPENDENCIES][] = ['onCalculateDependencies'];
    return $events;
  }

  /**
   * Reacts on CALCULATE_DEPENDENCIES event.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   Event.
   *
   * @throws \Exception
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    if (!$this->moduleHanlder->moduleExists('file')) {
      return;
    }
    $entity = $event->getEntity();

    if (FALSE === ($entity instanceof ContentEntityInterface)) {
      return;
    }

    $files = $this->getAttachedFiles($entity);
    foreach ($files as $file) {
      $this->addDependency($event, $file);
    }
  }

  /**
   * Builds list of attached files.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return \Drupal\file\Entity\File[]
   *   Files list.
   */
  protected function getAttachedFiles(EntityInterface $entity): array {
    $criteria = new Condition('AND');
    $criteria->condition('type', $entity->getEntityTypeId())
      ->condition('count', '0', '>')
      ->condition('module', ['editor'], 'in')
      ->condition('id', $entity->id());

    $rows = $this->database->select('file_usage', 'f')
      ->fields('f', ['fid'])
      ->condition($criteria)
      ->execute()
      ->fetchAllAssoc('fid');

    if (empty($rows)) {
      return [];
    }

    $fids = array_keys($rows);

    return File::loadMultiple($fids);
  }

  /**
   * Adds file as dependency.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   Event.
   * @param \Drupal\file\Entity\File $file
   *   File.
   *
   * @throws \Exception
   */
  protected function addDependency(CalculateEntityDependenciesEvent $event, File $file): void {
    if ($event->getStack()->hasDependency($file->uuid())) {
      return;
    }

    $file_wrapper = new DependentEntityWrapper($file);
    $local_dependencies = [];
    $file_wrapper_dependencies = $this->getCalculator()
      ->calculateDependencies($file_wrapper, $event->getStack(), $local_dependencies);
    $this->mergeDependencies($file_wrapper, $event->getStack(), $file_wrapper_dependencies);
    $event->addDependency($file_wrapper);
  }

}
