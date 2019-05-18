<?php

namespace Drupal\carerix_form\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\carerix_form\CarerixFormFieldsBase;
use Drupal\carerix_form\CarerixFormFieldsOpen;

/**
 * Class CRForm.
 *
 * This form embeds a CarerixForm entity instance as form.
 *
 * @package Drupal\carerix_form\Form
 */
class CRForm extends CRFormBase {

  /**
   * Check whether or not the form may be submitted through a publication.
   *
   * @param int $pubId
   *   Publication ID.
   *
   * @return bool
   *   Returns TRUE or FALSE.
   */
  protected function publicationIsAvailable($pubId) {

    /** @var \Carerix_Api_Rest_Entity_CRPublication $crPublication */
    $crPublication = $this->carerix->getEntityById('Publication', $pubId);

    if ($crPublication) {

      if (!isset($crPublication->publicationEnd)) {
        return TRUE;
      }

      $dateNow = new DrupalDateTime();
      $dateEnd = new DrupalDateTime($crPublication->publicationEnd);

      return $dateNow < $dateEnd;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'carerix_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $carerixFormId = NULL, $pubId = NULL) {

    // Check availability of publication.
    if ($pubId == NULL || !$this->publicationIsAvailable($pubId)) {

      // Fallback to default Carerix form.
      $carerixFormId = CarerixFormFieldsOpen::NAME;

      if ($pubId != NULL) {
        // Set notification.
        drupal_set_message($this->t('The job with ID %id is no longer available.', [
          '%id' => $pubId,
        ]), 'error', FALSE);
        // Render additional markup.
        $form['notify_open'] = [
          '#type' => 'markup',
          '#prefix' => '<h2>',
          '#markup' => $this->t('Open application'),
          '#suffix' => '</h2>',
        ];
      }
    }
    else {
      $form['pub_id'] = [
        '#type' => 'hidden',
        '#value' => $pubId,
      ];
    }

    // Get Carerix form config entity type & form field settings.
    $configEntity = \Drupal::entityTypeManager()->getStorage('carerix_form')->load($carerixFormId);

    // Make sure config entity is loaded before proceeding.
    if (!$configEntity) {
      return $form;
    }
    else {
      // Get config entity instance settings & tree from YML.
      $settings = $configEntity->getSettings();
      $tree = $this->carerixFormFields->getAll();

      // Keep track of meta data.
      $form['#file_fields'] = [];
      $form['#url_fields'] = [];

      // Build form.
      foreach ($settings as $id => $fieldGroup) {
        // Add form field group.
        $form[$id] = [
          '#title' => $tree[$id]['label'],
          '#type' => $tree[$id]['type'],
        ];

        foreach ($fieldGroup as $fieldId => $field) {
          // Check for enabled field.
          if ($field['enabled']) {
            // Add form fields.
            $form[$id][$fieldId] = [
              '#title' => $tree[$id]['mappings'][$fieldId]['label'],
              '#type' => $tree[$id]['mappings'][$fieldId]['type'],
              '#required' => $field['mandatory'] ? TRUE : FALSE,
              '#description' => isset($tree[$id]['mappings'][$fieldId]['description']) ? $tree[$id]['mappings'][$fieldId]['description'] : NULL,
            ];
            // Check further checks necessity.
            if (!isset($tree[$id]['mappings'][$fieldId]['mappings']['data_node_type'])) {
              continue;
            }
            // Check for file uploads.
            if ($tree[$id]['mappings'][$fieldId]['type'] == CarerixFormFieldsBase::FORM_FIELD_TYPE_FILE) {
              // File rules.
              $ext = $tree[$id]['mappings'][$fieldId]['ext'];
              $size = $tree[$id]['mappings'][$fieldId]['size'];
              // File additions.
              $form[$id][$fieldId] = [
                '#description' => str_replace(' ', ', ', strtoupper($ext)) . ' (' . $size . 'MB)',
                '#upload_location' => "temporary://carerix/",
                '#upload_validators' => [
                  'file_validate_size' => [$size * 1024 * 1024, 0],
                  'file_validate_extensions' => [$ext],
                ],
              ] + $form[$id][$fieldId];
              // Add a file field key mapped with data node id.
              $form['#file_fields'][$fieldId] = $field['mapping'];
            }
            // Check for url fields.
            elseif ($tree[$id]['mappings'][$fieldId]['type'] == CarerixFormFieldsBase::FORM_FIELD_TYPE_URL) {
              $form['#url_fields'][$fieldId] = $field['mapping'];
            }
          }
        }
      }

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    if (isset($values['emailAddress'])) {
      // Validate email.
      if (!$this->emailValidator->isValid($values['emailAddress'])) {
        $form_state->setErrorByName('emailAddress', $this->t('%email is an invalid email address.', [
          '%email' => $values['emailAddress'],
        ]));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Set vars.
    $values = $form_state->cleanValues()->getValues();
    $files = $urls = [];

    // Isolate file fields & remove from values.
    if (isset($form['#file_fields'])) {
      foreach ($form['#file_fields'] as $fileField => $dataNodeId) {
        // Check existing upload.
        if (isset($values[$fileField][0])) {
          // Set fid from values & data node id.
          $files[$fileField]['fid'] = $values[$fileField][0];
          $files[$fileField]['data_node_id'] = $dataNodeId;
          // Unset file in values.
          unset($values[$fileField]);
        }
      }
    }

    // Isolate url fields & remove from values.
    if (isset($form['#url_fields'])) {
      foreach ($form['#url_fields'] as $urlField => $dataNodeId) {
        // Check existing upload.
        if (isset($values[$urlField])) {
          // Set url from values & data node id.
          $urls[$urlField]['url'] = $values[$urlField];
          $urls[$urlField]['data_node_id'] = $dataNodeId;
          // Unset url in values.
          unset($values[$urlField]);
        }
      }
    }

    // Create Carerix employee.
    $this->carerix->createEmployee($values, $files, $urls);

    drupal_set_message($this->t('Thank you for your application.'));

    if (isset($this->redirectRouteName) && !empty($this->redirectRouteName)) {
      $form_state->setRedirect($this->redirectRouteName);
    }
  }

}
