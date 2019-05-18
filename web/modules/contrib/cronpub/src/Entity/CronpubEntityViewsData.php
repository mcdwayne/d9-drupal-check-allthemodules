<?php

/**
 * @file
 * Contains \Drupal\cronpub\Entity\CronpubEntity.
 */

namespace Drupal\cronpub\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Cronpub Task entities.
 */
class CronpubEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['cronpub_entity']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Cronpub Task'),
      'help' => $this->t('The Cronpub Task ID.'),
    );

    return $data;
  }

}
