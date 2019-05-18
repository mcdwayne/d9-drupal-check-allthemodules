<?php

namespace Drupal\autoban\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\autoban\Controller\AutobanController;

/**
 * Provides a listing of autoban entities.
 *
 * @package Drupal\autoban\Controller
 *
 * @ingroup autoban
 */
class AutobanListBuilder extends ConfigEntityListBuilder {

  private $banProviderList = NULL;

  /**
   * Get ban providers list.
   *
   * @return array
   *   An array ban providers name.
   */
  private function getBanProvidersList() {
    $controller = new AutobanController();
    $providers = [];
    $banManagerList = $controller->getBanProvidersList();
    if (!empty($banManagerList)) {
      foreach ($banManagerList as $id => $item) {
        $providers[$id] = $item['name'];
      }
    }

    return $providers;
  }

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    $header['id'] = $this->t('Id');
    $header['type'] = $this->t('Type');
    $header['message'] = $this->t('Message pattern');
    $header['referer'] = $this->t('Referrer');
    $header['threshold'] = $this->t('Threshold');
    $header['user_type'] = $this->t('User type');
    $header['provider'] = $this->t('Provider');

    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the entity.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['type'] = $entity->type;
    $row['message'] = $entity->message;
    $row['referer'] = $entity->referer;
    $row['threshold'] = $entity->threshold;

    $controller = new AutobanController();
    $row['user_type'] = $controller->userTypeList($entity->user_type ?: 0);

    if (!$this->banProviderList) {
      $this->banProviderList = $this->getBanProvidersList();
    }

    if (!empty($this->banProviderList) && isset($this->banProviderList[$entity->provider])) {
      $row['provider'] = $this->banProviderList[$entity->provider];
    }
    else {
      // If ban provider module is disabled.
      $row['provider'] = t('Inactive provider %provider', ['%provider' => $entity->provider]);
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * Operations list in the entity listing.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the operations.
   */
  public function getOperations(EntityInterface $entity) {
    $operations = $this->getDefaultOperations($entity);

    $rule = $entity->id();
    $destination = drupal_get_destination();

    $operations['test'] = [
      'title' => $this->t('Test'),
      'url' => Url::fromRoute('autoban.test', ['rule' => $rule], ['query' => $destination]),
      'weight' => 20,
    ];
    $operations['ban'] = [
      'title' => $this->t('Ban'),
      'url' => Url::fromRoute('autoban.ban', ['rule' => $rule], ['query' => $destination]),
      'weight' => 30,
    ];
    $operations['clone'] = [
      'title' => $this->t('Clone'),
      'url' => Url::fromRoute('entity.autoban.add_form', ['rule' => $rule], ['query' => $destination]),
      'weight' => 40,
    ];

    uasort($operations, '\\Drupal\\Component\\Utility\\SortArray::sortByWeightElement');
    return $operations;
  }

}
