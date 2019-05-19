<?php

namespace Drupal\webform_quiz\Model;


use Drupal;
use Drupal\webform\Entity\WebformSubmission;

class WebformSubmissionsHelper {

  /**
   * @var \Drupal\webform\Entity\Webform
   */
  protected $webform;

  /**
   * WebformSubmissions constructor.
   *
   * @param \Drupal\webform\WebformInterface $webform
   */
  public function __construct($webform) {
    $this->webform = $webform;
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface[]|\Drupal\webform\Entity\WebformSubmission[]
   */
  public function loadWebformSubmissions() {
    // todo: perform efq on webform submissions.
    $eq = Drupal::entityQuery('webform_submission')
      ->condition('webform_id', $this->webform->id());

    $ids = $eq->execute();
    $webform_submissions = WebformSubmission::loadMultiple($ids);

    return $webform_submissions;
  }

}
