<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hidden_tab\Entity\Helper\ConfigListBuilderBase;

/**
 * Provides a listing of hidden_tab_placement entities.
 *
 * @see \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface
 */
class HiddenTabPlacementListBuilder extends ConfigListBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Id');
    $header['target_hidden_tab_page'] = $this->t('Page');
    $header['region'] = $this->t('Region');
    $header['weight'] = $this->t('Weight');
    $header['komponent_type'] = $this->t('Komponent Type');
    $header['komponent'] = $this->t('Komponent');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  protected function unsafeBuildRow(EntityInterface $entity): array {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface $entity */
    return parent::configRowsBuilder($entity, [
      'id',
      'target_hidden_tab_page',
      'target_entity_type',
      'target_entity_bundle',
      'region',
      'weight',
      'komponent',
      'komponent_type',
    ]);
  }

}
