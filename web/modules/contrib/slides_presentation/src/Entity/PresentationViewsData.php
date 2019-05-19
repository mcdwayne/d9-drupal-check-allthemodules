<?php

namespace Drupal\slides_presentation\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Presentation entities.
 */
class PresentationViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['slides_presentation']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Presentation'),
      'help' => $this->t('The Presentation ID.'),
    ];

    return $data;
  }

}
