<?php

namespace Drupal\cloudwords\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Cloudwords translatable entities.
 */
class CloudwordsTranslatableViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['cloudwords_translatable']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Cloudwords translatable'),
      'help' => $this->t('The Cloudwords translatable ID.'),
    ];

//    $data['cloudwords_translatable']['language']['field']['id'] = 'language';
//    $data['cloudwords_translatable']['language']['field']['options callback'] = 'cloudwords_textgroup_options_list';
    $data['cloudwords_translatable']['translation_status'] = [
      'title' => 'Translation Status',
      'help' => 'Displays the translation status of an item.',
      'field' => [
        'id' => 'cloudwords_translatable_translation_status_field',
      ],
      'filter' => [
        'id' => 'cloudwords_translatable_translation_status_filter',
      ],
    ];

    $data['cloudwords_translatable']['status'] = [
      'title' => 'Queue Status',
      'help' => 'Displays the queue status of of a translatable.',
      'filter' => [
        'id' => 'cloudwords_translatable_status_filter',
      ],
    ];

    $data['cloudwords_translatable']['language'] = [
      'title' => 'Target Language',
      'field' => [
        'id' => 'cloudwords_translatable_target_language_field',
      ],
      'filter' => [
        'id' => 'cloudwords_translatable_target_language_filter',
      ],
    ];

    $data['cloudwords_translatable']['added_to_project'] = [
      'title' => 'Added to Project',
      'help' => 'Field to show whether or not an asset has been added to the current project.',
      'field' => [
        'id' => 'cloudwords_translatable_added_to_project_field',
      ],
      'filter' => [
        'id' => 'cloudwords_translatable_added_to_project_filter',
      ],
    ];

    $data['cloudwords_translatable']['bundle'] = [
      'title' => 'Entity Bundle',
      'help' => 'Field to show entity bundle.',
      'field' => [
        'id' => 'cloudwords_translatable_bundle_field',
      ],
      'filter' => [
        'id' => 'cloudwords_translatable_bundle_filter',
      ],
    ];

    $data['cloudwords_translatable']['textgroup']['filter']['id'] = 'cloudwords_translatable_textgroup_filter';

    return $data;
  }

}
