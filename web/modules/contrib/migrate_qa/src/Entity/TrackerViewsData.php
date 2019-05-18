<?php

namespace Drupal\migrate_qa\Entity;

/**
 * Provides Views data for Migrate QA Tracker entities.
 */
class TrackerViewsData extends EntityReferenceViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['migrate_qa_tracker']['flags_summary'] = [
      'title' => t('Flags Summary'),
      'field' => [
        'id' => 'migrate_qa_tracker_flags_summary',
      ],
    ];

    return $data;
  }

}
