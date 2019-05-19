<?php

namespace Drupal\webform_rest\Plugin\rest\resource;

use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Creates a resource for retrieving webform submission data.
 *
 * @RestResource(
 *   id = "webform_rest_submission",
 *   label = @Translation("Webform Submission"),
 *   uri_paths = {
 *     "canonical" = "/webform_rest/{webform_id}/submission/{sid}"
 *   }
 * )
 */
class WebformSubmissionResource extends ResourceBase {

  /**
   * Retrieve submission data.
   *
   * @param string $webform_id
   *   Webform ID.
   *
   * @param int $sid
   *   Submission ID.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   HTTP response object containing webform submission.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function get($webform_id, $sid) {

    if (empty($webform_id) || empty($sid)) {
      $errors = [
        'error' => [
          'message' => 'Both webform ID and submission ID are required.'
        ]
      ];
      return new ModifiedResourceResponse($errors);
    }

    // Load the webform submission.
    $webform_submission = WebformSubmission::load($sid);

    // Check for a submission.
    if (!empty($webform_submission)) {
      $submission_webform_id = $webform_submission->get('webform_id')->getString();

      // Check webform_id.
      if ($submission_webform_id == $webform_id) {

        // Grab submission data.
        $data = $webform_submission->getData();

        $response = [
          'entity' => $webform_submission,
          'data' => $data
        ];

        // Return the submission.
        return new ModifiedResourceResponse($response);
      }
    }

    throw new NotFoundHttpException(t("Can't load webform submission."));

  }
   /**
   * Update submission data.
   *
   * @param string $webform_id
   *   Webform ID.
   *
   * @param int $sid
   *   Submission ID.
   *
   * @param array $webform_data
   *   Webform field data.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   HTTP response object containing webform submission.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function patch($webform_id, $sid, array $webform_data) {

    if (empty($webform_id) || empty($sid)) {
      $errors = [
        'error' => [
          'message' => 'Both webform ID and submission ID are required.'
        ]
      ];
      return new ModifiedResourceResponse($errors);
    }

    if (empty($webform_data)) {
      $errors = [
        'error' => [
          'message' => 'No data has been submitted.'
        ]
      ];
      return new ModifiedResourceResponse($errors);
    }

    // Load the webform submission.
    $webform_submission = WebformSubmission::load($sid);

    // Check for a submission.
    if (!empty($webform_submission)) {
      $submission_webform_id = $webform_submission->get('webform_id')->getString();

      // Check webform_id.
      if ($submission_webform_id == $webform_id) {

        foreach ($webform_data as $field => $value) {
          $webform_submission->setElementData($field, $value);
        }

        $errors = WebformSubmissionForm::validateWebformSubmission($webform_submission);

        // Check there are no validation errors.
        if (!empty($errors)) {
          $errors = ['error' => $errors];
          return new ModifiedResourceResponse($errors);
        }
        else {
          // Return submission ID.
          $webform_submission = WebformSubmissionForm::submitWebformSubmission($webform_submission);
        }

        // Return submission ID.
        return new ModifiedResourceResponse(['sid' => $webform_submission->id()]);
      }
    }

    throw new NotFoundHttpException(t("Can't load webform submission."));
  }

}
