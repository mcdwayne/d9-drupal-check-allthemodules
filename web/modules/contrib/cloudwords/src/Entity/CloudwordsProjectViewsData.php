<?php

namespace Drupal\cloudwords\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Cloudwords project entities.
 */
class CloudwordsProjectViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['cloudwords_project']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Cloudwords project'),
      'help' => $this->t('The Cloudwords project ID.'),
    ];

    $data['cloudwords_project']['status'] = [
      'title' => 'Status',
      'help' => 'Displays the translation status of an item.',
      'field' => [
        'id' => 'cloudwords_project_status_field',
      ],
      'filter' => [
        'id' => 'cloudwords_project_translation_status_filter',
      ],
    ];

    $data['cloudwords_project']['source_language'] = [
      'title' => 'Source language',
      'help' => 'The project source language.',
      'field' => [
        'id' => 'cloudwords_project_source_language_field',
      ],
//      'filter' => array(
//        'id' => 'cloudwords_project_source_language_filter',
//      ),
    ];

    $data['cloudwords_project']['target_languages'] = [
      'title' => 'Target Languages',
      'help' => 'The project target languages.',
      'field' => [
        'id' => 'cloudwords_project_target_languages_field',
      ],
//      'filter' => array(
//        'id' => 'cloudwords_project_target_languages_filter',
//      ),
    ];

    return $data;
  }

}
