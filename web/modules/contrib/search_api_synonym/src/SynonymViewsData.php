<?php

namespace Drupal\search_api_synonym;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Synonym entities.
 */
class SynonymViewsData extends EntityViewsData {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();


    $data['search_api_synonym_field_data']['table']['base']['help'] = $this->t('Synonyms managed by Search API Synonyms module.');
    $data['search_api_synonym_field_data']['table']['base']['defaults']['field'] = 'word';
    $data['search_api_synonym_field_data']['table']['wizard_id'] = 'synonym';

    $data['search_api_synonym_field_data']['sid']['title'] = $this->t('Synonym ID');
    $data['search_api_synonym_field_data']['sid']['help'] = $this->t('The unique id of the synonym entity.');

    $data['search_api_synonym_field_data']['word']['title'] = $this->t('Word');
    $data['search_api_synonym_field_data']['word']['help'] = $this->t('The word we are defining synonyms for.');

    $data['search_api_synonym_field_data']['synonyms']['title'] = $this->t('Synonyms');
    $data['search_api_synonym_field_data']['synonyms']['help'] = $this->t('The synonyms to the word.');

    $data['search_api_synonym_field_data']['type']['title'] = $this->t('Type');
    $data['search_api_synonym_field_data']['type']['help'] = $this->t('The type of synonym. Either synonym or spelling error.');

    $data['search_api_synonym_field_data']['created']['title'] = $this->t('Create date');
    $data['search_api_synonym_field_data']['created']['help'] = $this->t('Date and time of when the synonym was created.');

    $data['search_api_synonym_field_data']['created_fulldata'] = [
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_fulldate',
      ],
    ];

    $data['search_api_synonym_field_data']['created_year_month'] = [
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    $data['search_api_synonym_field_data']['created_year'] = [
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];

    $data['search_api_synonym_field_data']['created_month'] = [
      'title' => $this->t('Created month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_month',
      ],
    ];

    $data['search_api_synonym_field_data']['created_day'] = [
      'title' => $this->t('Created day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_day',
      ],
    ];

    $data['search_api_synonym_field_data']['created_week'] = [
      'title' => $this->t('Created week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_week',
      ],
    ];

    $data['search_api_synonym_field_data']['changed']['title'] = $this->t('Updated date');
    $data['search_api_synonym_field_data']['changed']['help'] = $this->t('Date and time of when the synonym was last updated.');

    $data['search_api_synonym_field_data']['changed_fulldata'] = [
      'title' => $this->t('Changed date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_fulldate',
      ],
    ];

    $data['search_api_synonym_field_data']['changed_year_month'] = [
      'title' => $this->t('Changed year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year_month',
      ],
    ];

    $data['search_api_synonym_field_data']['changed_year'] = [
      'title' => $this->t('Changed year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year',
      ],
    ];

    $data['search_api_synonym_field_data']['changed_month'] = [
      'title' => $this->t('Changed month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_month',
      ],
    ];

    $data['search_api_synonym_field_data']['changed_day'] = [
      'title' => $this->t('Changed day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_day',
      ],
    ];

    $data['search_api_synonym_field_data']['changed_week'] = [
      'title' => $this->t('Changed week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_week',
      ],
    ];

    $data['search_api_synonym_field_data']['status']['title'] = $this->t('Active status');
    $data['search_api_synonym_field_data']['status']['help'] = $this->t('Whether the synonym is active and used by search engines or is it no active.');
    $data['search_api_synonym_field_data']['status']['filter']['label'] = $this->t('Active synonym status');
    $data['search_api_synonym_field_data']['status']['filter']['type'] = 'yes-no';

    $data['search_api_synonym_field_data']['uid']['title'] = $this->t('Author uid');
    $data['search_api_synonym_field_data']['uid']['help'] = $this->t('If you need more fields than the uid add the synonym: author relationship');
    $data['search_api_synonym_field_data']['uid']['relationship']['title'] = $this->t('Author');
    $data['search_api_synonym_field_data']['uid']['relationship']['help'] = $this->t('The User ID of the synonym\'s author.');
    $data['search_api_synonym_field_data']['uid']['relationship']['label'] = $this->t('author');

    return $data;
  }

}
