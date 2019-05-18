<?php

namespace Drupal\blizz_bulk_creator\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkcreateConfiguration.
 *
 * Provides an overview page of existing bulkcreate configurations.
 *
 * @package Drupal\blizz_bulk_creator\ListBuilder
 */
class BulkcreateConfiguration extends ConfigEntityListBuilder {

  /**
   * Custom service to ease administrative tasks.
   *
   * @var \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface
   */
  protected $administrationHelper;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('blizz_bulk_creator.administration_helper'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * BulkcreateConfiguration constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface $administration_helper
   *   Custom service to ease administrative tasks.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   Drupal's service to store session related data.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    BulkcreateAdministrationHelperInterface $administration_helper,
    PrivateTempStoreFactory $temp_store_factory
  ) {
    parent::__construct($entity_type, $storage);
    $this->administrationHelper = $administration_helper;
    $tempStore = $temp_store_factory->get('blizz_bulk_creator.stepdata');
    foreach (['admin_title', 'custom_entity_name', 'bundle', 'bulkcreate_field'] as $key) {
      $tempStore->delete($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Name');
    $header['usages'] = t('Count usages');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface $entity */
    $row['label'] = $entity->label();
    $row['usages'] = count($this->administrationHelper->getBulkcreateUsages($entity));
    return $row + parent::buildRow($entity);
  }

}
