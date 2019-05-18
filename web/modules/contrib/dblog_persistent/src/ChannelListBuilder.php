<?php

namespace Drupal\dblog_persistent;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Persistent Log Message Type entities.
 */
class ChannelListBuilder extends ConfigEntityListBuilder {

  /**
   * @var \Drupal\dblog_persistent\DbLogPersistentStorageInterface
   */
  protected $logStorage;

  /**
   * DbLogPersistentTypeListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   * @param \Drupal\dblog_persistent\DbLogPersistentStorageInterface $loader
   */
  public function __construct(EntityTypeInterface $entity_type,
                              EntityStorageInterface $storage,
                              DbLogPersistentStorageInterface $loader) {
    parent::__construct($entity_type, $storage);
    $this->logStorage = $loader;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function createInstance(ContainerInterface $container,
                                        EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('dblog_persistent.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Name');
    $header['types'] = $this->t('Filters');
    $header['count'] = $this->t('Events');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\dblog_persistent\Entity\ChannelInterface $entity */
    $row['label'] = $entity->label();
    $filters = [];
    if ($types = $entity->getTypes()) {
      $filters[] = [
        '#theme' => 'item_list',
        '#prefix' => $this->t('Types: '),
        '#context' => ['list_style' => 'comma-list'],
        '#items' => $types,
      ];
    }
    if ($levels = $entity->getLevels()) {
      $filters[] = [
        '#theme' => 'item_list',
        '#prefix' => $this->t('Severity: '),
        '#context' => ['list_style' => 'comma-list'],
        '#items' => \array_intersect_key(RfcLogLevel::getLevels(), $levels),
      ];
    }
    if ($message = $entity->getMessage()) {
      $filters[] = $this->t('Message contains: %text', [
        '%text' => $message,
      ]);
    }
    if ($filters) {
      $row['summary']['data'] = [
        '#theme' => 'item_list',
        '#items' => $filters,
      ];
    }
    else {
      $row['summary'] = $this->t('All events');
    }

    $row['count'] = $this->logStorage->countChannel($entity->id());
    if ($row['count']) {
      /** @var \Drupal\dblog_persistent\Entity\Channel $entity */
      $row['count'] = ['data' => [
        '#type' => 'link',
        '#url' => $entity->toUrl('canonical'),
        '#title' => $row['count'],
      ]];
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    // If the channel is not empty, we can clear it.
    if ($this->logStorage->countChannel($entity->id())) {
      $operations['clear'] = [
        'title' => $this->t('Clear'),
        'url' => $entity->toUrl('clear-form'),
        'weight' => 20,
      ];
    }
    return $operations;
  }

}
