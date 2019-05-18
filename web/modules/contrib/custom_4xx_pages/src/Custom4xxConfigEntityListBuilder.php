<?php

namespace Drupal\custom_4xx_pages;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Custom 4xx Configuration Item entities.
 */
class Custom4xxConfigEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    // $header['id'] = $this->t('Machine name');
    $header['4xx_type'] = $this->t('4xx Type');
    $header['4xx_path'] = $this->t('Path To Apply On');
    $header['4xx_destination'] = $this->t('Custom 4xx Path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    // $row['id'] = $entity->id();
    $row['4xx_type'] = $entity->get('custom_4xx_type');
    $row['4xx_path'] = $entity->get('custom_403_path_to_apply');
    $row['4xx_destination'] = $entity->get('custom_403_page_path');


    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
