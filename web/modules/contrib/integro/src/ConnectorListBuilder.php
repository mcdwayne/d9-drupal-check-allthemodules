<?php

namespace Drupal\integro;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder.
 */
class ConnectorListBuilder extends ConfigEntityListBuilder {

  /**
   * The integration manager.
   *
   * @var \Drupal\integro\IntegrationManagerInterface
   */
  protected $integrationManager;

  /**
   * Constructs a new BlockListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\integro\IntegrationManagerInterface $integration_manager
   *   The integration manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, IntegrationManagerInterface $integration_manager) {
    parent::__construct($entity_type, $storage);
    $this->integrationManager = $integration_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('integro_integration.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->entityType->getLabel();
    $header['integration'] = $this->t('Integration');
    $header['client'] = $this->t('Client');
    $header['auth'] = $this->t('Auth State');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $integration = $entity->getIntegration();
    $row['label'] = $entity->toLink($entity->label(), 'edit-form');
    $row['integration'] = $integration->getDefinition()->getLabel();
    $row['client'] = $integration->getDefinition()->getClientPlugin()->getLabel();
    if ($entity->get('authorized')) {
      if ($entity->get('auth_data')['expiration'] > time()) {
        $row['auth'] = $this->t('Authorized');
      }
      else {
        $row['auth'] = $this->t('Expired');
      }
    }
    else {
      $row['auth'] = $this->t('Not authorized');
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    if (isset($operations['authorize'])) {
      $operations['authorize'] = [
        'title' => t('Authorize'),
        'weight' => 20,
        'url' => $entity->toUrl('authorize'),
      ];
    }

    if (isset($operations['edit'])) {
      $operations['edit'] = [
        'title' => t('Edit'),
        'weight' => 30,
        'url' => $entity->toUrl('edit-form'),
      ];
    }

    if (isset($operations['delete'])) {
      $operations['delete'] = [
        'title' => t('Delete'),
        'weight' => 35,
        'url' => $entity->toUrl('delete-form'),
      ];
    }

    // Sort the operations to normalize link order.
    uasort($operations, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement',
    ]);

    return $operations;
  }

}
