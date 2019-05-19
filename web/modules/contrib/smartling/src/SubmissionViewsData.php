<?php

/**
 * @file
 * Contains \Drupal\smartling\SubmissionViewsData.
 */

namespace Drupal\smartling;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the smartling submission entity type.
 */
class SubmissionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['smartling_submission']['table']['base']['help'] = $this->t('Smartling submissions are stored translations of entities.');

    $data['smartling_submission']['id']['argument']['name field'] = 'title';
    $data['smartling_submission']['id']['argument']['numeric'] = TRUE;

    $data['smartling_submission']['status']['filter']['id'] = 'list_field';
    $data['smartling_submission']['status']['filter']['field_name'] = 'status';

    $data['smartling_submission']['status']['argument']['id'] = 'number_list_field';
    $data['smartling_submission']['status']['argument']['field_name'] = 'status';

    $data['smartling_submission']['submitter']['relationship']['title'] = t('Author');
    $data['smartling_submission']['submitter']['relationship']['help'] = t("The User ID of the submission's author.");
    $data['smartling_submission']['submitter']['relationship']['label'] = t('author');

    return $data;
  }

}
