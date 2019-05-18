<?php

namespace Drupal\dcat\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Agent entities.
 */
class DcatAgentViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['dcat_agent']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Agent'),
      'help' => $this->t('The Agent ID.'),
    );

    return $data;
  }

}
