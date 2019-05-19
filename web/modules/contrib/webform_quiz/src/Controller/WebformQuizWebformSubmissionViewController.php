<?php

namespace Drupal\webform_quiz\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Controller\WebformSubmissionViewController;

class WebformQuizWebformSubmissionViewController extends WebformSubmissionViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $webform_submission, $view_mode = 'default', $langcode = NULL) {
    $build = parent::view($webform_submission, $view_mode, $langcode);
    return $build;
  }

}
