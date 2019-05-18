<?php

namespace Drupal\blizz_bulk_creator\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkcreateUsage.
 *
 * Provides an overview page of existing bulkcreate usages.
 *
 * @package Drupal\blizz_bulk_creator\ListBuilder
 */
class BulkcreateUsage extends ConfigEntityListBuilder {

  /**
   * The storage interface for bulkcreate configurations.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $bulkcreateConfigurationStorage;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager')->getStorage('bulkcreate_configuration'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * BulkcreateUsage constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityStorageInterface $bulkcreate_configuration_storage
   *   The bulkcreate_configuration storage class.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   Drupal's service to store session related data.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    EntityStorageInterface $bulkcreate_configuration_storage,
    PrivateTempStoreFactory $temp_store_factory
  ) {
    parent::__construct($entity_type, $storage);
    $this->bulkcreateConfigurationStorage = $bulkcreate_configuration_storage;
    $tempStore = $temp_store_factory->get('blizz_bulk_creator.stepdata');
    $valuesToClear = [
      'bulkcreate_configuration',
      'entity_type_id',
      'bundle',
      'target_field',
    ];
    foreach ($valuesToClear as $key) {
      $tempStore->delete($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Entity / bundle');
    $header['config'] = t('Bulkcreate configuration');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['config'] = $this->bulkcreateConfigurationStorage->load($entity->get('bulkcreate_configuration'))->label();
    return $row + parent::buildRow($entity);
  }

}
