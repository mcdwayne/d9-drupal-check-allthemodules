<?php

namespace Drupal\eloqua_api_redux\Service;

/**
 * Class Forms.
 *
 * See https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/api-application-1.0-forms.html.
 *
 * @package Drupal\eloqua_api_redux\Service
 */
class Forms {

  /**
   * Eloqua Api Client.
   *
   * @var \Drupal\eloqua_api_redux\Service\EloquaApiClient
   */
  protected $client;

  /**
   * Contact constructor.
   *
   * @param \Drupal\eloqua_api_redux\Service\EloquaApiClient $client
   *   Eloqua API Client.
   */
  public function __construct(EloquaApiClient $client) {
    $this->client = $client;
  }

  /**
   * Create form data for a single form.
   *
   * @param int $formId
   *   Id of the form.
   * @param array $formData
   *   Form data options.
   *   - currentStatus(optional): string
   *     The form's current status. This is a read-only property.
   *   - fieldValues(optional): array
   *     A list of key/value pairs identifying the form data
   *   - id(optional): string
   *     Id of the form. This is a read-only property.
   *   - processingStepErrors(optional): array
   *     A list of Processing Step Errors occurred in current form submission.
   *     This is a read-only property
   *   - submittedAt(optional): string
   *     Unix timestamp for the date and time the form data was submitted.
   *     This is a read-only property.
   *   - submittedByContactId(optional): string
   *     Id of the contact that submitted the form.
   *     This is a read-only property.
   *   - type(optional): string
   *     The asset's type in Eloqua. This is a read-only property.
   *
   *   See: https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/op-api-rest-1.0-data-form-id-post.html.
   *
   * @return array
   *   Created Form.
   */
  public function createFormData($formId, array $formData) {
    $endpointUrl = '/api/REST/2.0/data/form/' . $formId;
    $formData = array_filter($formData);

    // Dont do anything if the formData is empty.
    if (empty($formData)) {
      return [];
    }

    $newContact = $this->client->doEloquaApiRequest('POST', $endpointUrl, $formData);
    if (!empty($newContact)) {
      return $newContact;
    }

    return [];
  }

  /**
   * Retrieve a form.
   *
   * @param int $formId
   *   Id of the form.
   * @param array $queryParams
   *   Array of query params.
   *   - depth(optional): string
   *     Level of detail returned by the request. Eloqua APIs can retrieve
   *     entities at three different levels of depth: minimal, partial, and
   *     complete. Any other values passed are reset to complete by default.
   *
   *   See: https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/op-api-rest-1.0-assets-form-id-get.html.
   *
   * @return array
   *   Form array.
   */
  public function getForm($formId, array $queryParams = []) {
    $endpointUrl = '/api/REST/2.0/assets/form/' . $formId;

    $form = $this->client->doEloquaApiRequest('GET', $endpointUrl, NULL, $queryParams);

    if (!empty($form)) {
      return $form;
    }

    return [];
  }

  /**
   * Retrieve a list of forms.
   *
   * @param array $queryParams
   *   Array of query params.
   *
   *   - count(optional): integer
   *     Maximum number of entities to return. Must be less than or equal to
   *     1000 and greater than or equal to 1.
   *   - depth(optional): string
   *     Level of detail returned by the request. Eloqua APIs can retrieve
   *     entities at three different levels of depth: minimal, partial, and
   *     complete. Any other values passed are reset to complete by default.
   *   - lastUpdatedAt(optional): integer
   *     The date and time the form was last updated.
   *   - orderBy(optional): string
   *     Specifies the field by which list results are ordered.
   *   - page(optional): integer
   *     Specifies which page of entities to return (the count parameter defines
   *     the number of entities per page). If the page parameter is not supplied,
   *     1 will be used by default.
   *   - search(optional): string
   *     Specifies the search criteria used to retrieve entities. See the
   *     tutorial for information about using this parameter.
   *
   *   See: https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAC/op-api-rest-2.0-assets-forms-get.html.
   *
   * @return array
   *   Form array.
   */
  public function getForms(array $queryParams = []) {
    $endpointUrl = '/api/REST/2.0/assets/forms';

    $forms = $this->client->doEloquaApiRequest('GET', $endpointUrl, NULL, $queryParams);

    return $forms;
  }

  /**
   * Helper method to get all fields for a given form.
   *
   * @param int $formId
   *   Form ID.
   *
   * @return array
   *   Fields.
   */
  public function getFieldsRaw($formId) {
    $fields = [];
    $form = $this->getForm($formId);

    if (!empty($form) && $form['elements']) {
      foreach ($form['elements'] as $element) {
        if ($element['type'] &&
            $element['type'] == 'FormField' &&
            $element['displayType'] != 'submit') {
          $fields[] = $element;
        }
      }
    }

    return $fields;
  }

  /**
   * Helper method to get all Dummy fields for a given form.
   *
   * @param int $formId
   *   Form ID.
   *
   * @return array
   *   Fields.
   */
  public function getFieldsDummy($formId) {
    $fields = $this->getFieldsRaw($formId);
    $dummyFields = [];

    if (!empty($fields)) {
      foreach ($fields as $field) {
        $dummyFields['fieldValues'][] = [
          'type' => $field['type'],
          'id' => $field['id'],
          'value' => '',
        ];
      }
    }

    return $dummyFields;
  }

}
