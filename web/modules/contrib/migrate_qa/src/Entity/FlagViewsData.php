<?php

namespace Drupal\migrate_qa\Entity;

/**
 * Provides Views data for Migrate QA Flag entities.
 */
class FlagViewsData extends EntityReferenceViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['migrate_qa_flag']['details_count'] = [
      'title' => t('Details Count'),
      'field' => [
        'id' => 'migrate_qa_flag_details_count',
      ],
    ];

    return $data;
  }

}
