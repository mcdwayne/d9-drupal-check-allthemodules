<?php

/**
 * @file
 * Contains \Drupal\hierarchical_config\Entity\HierarchicalConfiguration.
 */

namespace Drupal\hierarchical_config\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Hierarchical configuration entities.
 */
class HierarchicalConfigurationViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['hierarchical_configuration']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Hierarchical configuration'),
      'help' => $this->t('The Hierarchical configuration ID.'),
    );

    return $data;
  }

}
