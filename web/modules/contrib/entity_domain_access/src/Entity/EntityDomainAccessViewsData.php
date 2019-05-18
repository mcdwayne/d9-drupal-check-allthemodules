<?php

namespace Drupal\entity_domain_access\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Domain Access entities.
 */
class EntityDomainAccessViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $base_table = $this->entityType->getBaseTable() ?: $this->entityType->id();
    $data[$base_table][DOMAIN_ACCESS_FIELD]['field']['id'] = 'domain_access_field';
    $data[$base_table][DOMAIN_ACCESS_FIELD . '_target_id']['filter']['id'] = 'domain_access_filter';
    $data[$base_table][DOMAIN_ACCESS_FIELD . '_target_id']['argument']['id'] = 'domain_access_argument';

    // Current domain filter.
    $data[$base_table]['current_all'] = [
      'title' => t('Current domain'),
      'group' => t('Domain'),
      'filter' => [
        'field' => DOMAIN_ACCESS_FIELD . '_target_id',
        'id' => $this->entityType->id() . '_engine_domain_access_current_all_filter',
        'title' => t('Available on current domain'),
        'help' => t('Filters out @entity_type_label not available on current domain (published to current domain or all affiliates).', [
          '@entity_type_label' => $this->entityType->getLabel(),
        ]),
      ],
    ];

    return $data;
  }

}
