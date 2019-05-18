<?php

namespace Drupal\token_custom;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\RedirectDestinationTrait;

/**
 * Defines a class to build a listing of custom token entities.
 *
 * @see \Drupal\token_custom\Entity\TokenCustom
 */
class TokenCustomListBuilder extends EntityListBuilder {

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['machine_name'] = $this->t('Machine name');
    $header['type'] = $this->t('Type');
    $header['name'] = $this->t('Name');
    $header['description'] = t('Description');
    $header['content'] = t('Content');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['machine_name'] = $entity->id();
    $row['type'] = $entity->bundle();
    $row['name'] = $entity->label();
    $row['description'] = $entity->getDescription();
    $row['content'] = $entity->getRawContent();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if (isset($operations['edit'])) {
      $operations['edit']['query']['destination'] = $this->getRedirectDestination()->get();
    }
    return $operations;
  }

}
