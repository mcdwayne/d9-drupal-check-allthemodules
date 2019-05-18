<?php

namespace Drupal\commerce_xero\Controller;

use Drupal\commerce_xero\CommerceXeroDataTypeManager;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of strategy entities.
 */
class StrategyListBuilder extends ConfigEntityListBuilder {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Commerce xero data type manager.
   *
   * @var \Drupal\commerce_xero\CommerceXeroDataTypeManager
   */
  protected $dataTypeManager;

  /**
   * Initialize method.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type manager service.
   * @param \Drupal\commerce_xero\CommerceXeroDataTypeManager $dataTypeManager
   *   The commerce xero data type plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entityTypeManager, CommerceXeroDataTypeManager $dataTypeManager) {
    parent::__construct($entity_type, $storage);

    $this->entityTypeManager = $entityTypeManager;
    $this->dataTypeManager = $dataTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'commerce_xero';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('Identifier'),
      'name' => $this->t('Name'),
      'status' => $this->t('Status'),
      'payment_method' => $this->t('Payment method'),
      'xero_type' => $this->t('Xero type'),
      'bank_account' => $this->t('Bank account'),
      'revenue_account' => $this->t('Revenue account'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $gateway_label = t('Unknown');
    $xero_type_definition = $this->dataTypeManager->getDefinition($entity->get('xero_type'));

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->entityTypeManager
      ->getStorage('commerce_payment_gateway')
      ->load($entity->get('payment_gateway'));

    if ($payment_gateway) {
      $gateway_label = $payment_gateway->label();
    }

    $row = [
      'id' => $entity->id(),
      'name' => $entity->label(),
      'status' => $entity->get('status') ? $this->t('Enabled') : $this->t('Disabled'),
      'payment_gateway' => $gateway_label,
      'xero_type' => $xero_type_definition['label']->__toString(),
      'bank_account' => $entity->get('bank_account'),
      'revenue_account' => $entity->get('revenue_account'),
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
      $container->get('commerce_xero_data_type.manager')
    );
  }

}
