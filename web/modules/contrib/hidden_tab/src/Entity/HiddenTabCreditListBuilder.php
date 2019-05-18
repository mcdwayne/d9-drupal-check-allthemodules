<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\hidden_tab\Entity\Helper\EntityListBuilderBase;
use Drupal\hidden_tab\FUtility;
use Drupal\hidden_tab\Service\CreditChargingInterface;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the hidden tab credit entity type.
 */
class HiddenTabCreditListBuilder extends EntityListBuilderBase {

  /**
   * Credit service, to show credit property and whether if it is infinite.
   *
   * @var \Drupal\hidden_tab\Service\CreditChargingInterface
   */
  protected $creditService;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type,
                              EntityStorageInterface $storage,
                              Connection $database,
                              RedirectDestination $redirect_destination,
                              CreditChargingInterface $credit_service) {
    parent::__construct($entity_type, $storage, $database, $redirect_destination);
    $this->creditService = $credit_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container,
                                        EntityTypeInterface $entity_type) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('database'),
      $container->get('redirect.destination'),
      $container->get('hidden_tab.credit_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return static::header() + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  protected function unsafeBuildRow(EntityInterface $entity) {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabCreditInterface $entity */
    return $this->row($this->creditService, $entity);
  }

  /**
   * Header for row().
   *
   * @return array
   *   An array for row() headers.
   */
  public static function header(): array {
    $header = FUtility::refrencerEntityRowBuilderForEntityListHeaders();
    $header['credit'] = t('Credit');
    $header['infinite'] = t('Is Infinite');
    return $header;
  }

  /**
   * Helper to create a renderable row output of the entity.
   *
   * @param \Drupal\hidden_tab\Service\CreditChargingInterface $creditService
   *   To get credit amount.
   * @param \Drupal\hidden_tab\Entity\HiddenTabCreditInterface $entity
   *   Entity to render.
   *
   * @return array
   *   Renderable array output.
   */
  public static function row(CreditChargingInterface $creditService, HiddenTabCreditInterface $entity) {
    $row = FUtility::refrencerEntityRowBuilderForEntityList($entity, 'hidden_tab_credit');
    try {
      if (!$creditService->isValid($entity->credit())) {
        $row['credit'] = Utility::WARNING;
        $row['infinite'] = Utility::WARNING;
      }
      if ($creditService->isInfinite($entity->credit())) {
        $row['credit'] = t('Infinite');
        $row['infinite'] = Utility::TICK;
      }
      else {
        $row['credit'] = $entity->credit();
        $row['infinite'] = Utility::CROSS;
      }
    }
    catch (\Throwable $error0) {
      Utility::renderLog($error0, 'hidden_tab_credit', 'credit/infinite');
      $row['credit'] = Utility::WARNING;
      $row['infinite'] = Utility::WARNING;
    }
    return $row;
  }

}
