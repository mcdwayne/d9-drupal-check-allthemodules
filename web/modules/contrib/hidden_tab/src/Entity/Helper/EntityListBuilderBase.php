<?php

namespace Drupal\hidden_tab\Entity\Helper;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\hidden_tab\Utility;

/**
 * For listable entities, helps safely build a list of entities.
 */
abstract class EntityListBuilderBase extends EntityListBuilder {

  /**
   * Used by render().
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type,
                              EntityStorageInterface $storage,
                              Connection $database,
                              RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->database = $database;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();

    $total = $this->database
      ->query('SELECT COUNT(*) FROM {' . $this->entityTypeId . '}')
      ->fetchField();

    $build['summary']['#markup'] = $this->t('Total: @total', ['@total' => $total]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public final function buildRow(EntityInterface $entity) {
    try {
      return $this->unsafeBuildRow($entity) + parent::buildRow($entity);
    }
    catch (\Throwable $error0) {
      Utility::renderLog($error0, $this->entityTypeId, '~');
      $ret['label'] = $entity->id();
      for ($i = 0; $i < (count($this->buildHeader()) - 1); $i++) {
        $ret[] = Utility::CROSS;
      }
      return $ret;
    }
  }

  protected abstract function unsafeBuildRow(EntityInterface $entity);

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    return $operations;
  }

}
