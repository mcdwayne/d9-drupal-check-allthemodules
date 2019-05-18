<?php

namespace Drupal\graph_reference\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Graph edge entities.
 */
class GraphEdgeViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['graph_edge']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Graph edge'),
      'help' => $this->t('The Graph edge ID.'),
    );

    return $data;
  }

}
