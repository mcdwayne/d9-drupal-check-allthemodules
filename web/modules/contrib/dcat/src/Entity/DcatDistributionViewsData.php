<?php

namespace Drupal\dcat\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Distribution entities.
 */
class DcatDistributionViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['dcat_distribution']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Distribution'),
      'help' => $this->t('The Distribution ID.'),
    );

    return $data;
  }

}
