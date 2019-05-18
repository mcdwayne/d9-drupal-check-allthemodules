<?php

namespace Drupal\eform;


use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the eform_submission entity type.
 */
class EFormSubmissionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['eform_submission']['eform_submission_bulk_form'] = array(
      'title' => t('Bulk update'),
      'help' => t('Add a form element that lets you run operations on multiple EForm Submissions.'),
      'field' => array(
        'id' => 'bulk_form',
      ),
    );
    return $data;
  }

}
