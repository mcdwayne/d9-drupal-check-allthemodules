<?php

namespace Drupal\slides_presentation\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Slide entities.
 */
class SlideViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['slides_slide']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Slide'),
      'help' => $this->t('The Slide ID.'),
    ];

    return $data;
  }

}
