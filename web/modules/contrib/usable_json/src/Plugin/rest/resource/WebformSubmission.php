<?php

namespace Drupal\usable_json\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;

/**
 * Creates a resource for submitting a webform.
 *
 * @RestResource(
 *   id = "webform_rest_submit",
 *   label = @Translation("Webform Submit"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/webform_submission"
 *   }
 * )
 */
class WebformSubmission extends ResourceBase {

  /**
   * Responds to entity POST requests and saves the new entity.
   *
   * @param array $webform_data
   *   Webform field data and webform ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function post(array $webform_data) {

    // Basic check for webform ID.
    if (empty($webform_data['webform_id'])) {
      return new ModifiedResourceResponse('', 500);
    }

    // Convert to webform values format.
    $values = [
      'webform_id' => $webform_data['webform_id'],
      'entity_type' => !empty($webform_data['entity_type']) ? $webform_data['entity_type'] : NULL,
      'entity_id' => !empty($webform_data['entity_id']) ? $webform_data['entity_id'] : NULL,
      'in_draft' => FALSE,
      'uri' => '/webform/' . $webform_data['webform_id'] . '/api',
    ];

    // Don't submit webform ID.
    unset($webform_data['webform_id']);
    unset($webform_data['data']);

    foreach ($webform_data as $key => $data) {
      if (is_array($data)) {
        $dataValues = [];
        foreach ($data as $dataKey => $dataValue) {
          if ($dataValue) {
            $dataValues[] = $dataKey;
          }
        }
        $webform_data[$key] = $dataValues;
      }
    }

    $values['data'] = $webform_data;
    // Check webform is open.
    $webform = Webform::load($values['webform_id']);
    $is_open = WebformSubmissionForm::isOpen($webform);

    if ($is_open === TRUE) {
      // Validate submission.
      $errors = WebformSubmissionForm::validateFormValues($values);

      // Check there are no validation errors.
      if (!empty($errors)) {
        $errors = [
          'error' => TRUE,
          'valid' => FALSE,
          'validation_errors' => $errors,
        ];
        return new ModifiedResourceResponse($errors);
      }
      else {
        // Return submission ID.
        $webform_submission = WebformSubmissionForm::submitFormValues($values);
        return new ModifiedResourceResponse(['sid' => $webform_submission->id()]);
      }
    }
  }

}
