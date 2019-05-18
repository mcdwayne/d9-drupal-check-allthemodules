<?php

/**
 * @file
 * Contains \Drupal\javascript_libraries\Form\JavascriptLibrariesCustomAddForm.
 */

namespace Drupal\javascript_libraries\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Config\ConfigFactoryInterface;

class JavascriptLibrariesCustomAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'javascript_libraries_custom_add_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $library = '') {
    $form['#attached']['library'][] = 'javascript_libraries/javascript_libraries.default';
    \Drupal::configFactory()->getEditable('system.file')
      ->set('allow_insecure_uploads', 1)
      ->save();
    $custom = \Drupal::config('javascript_libraries.settings')
      ->get('javascript_libraries_custom_libraries');
    if (isset($library) && !empty($library)) {
      if (!array_key_exists($library, $custom)) {
        drupal_set_message("The library with following identifier doesnot exist");
        return;
      }
      $form['lib_id'] = array(
        '#type' => 'value',
        '#value' => $custom[$library]['id'],
      );
      $library = $custom[$library];
    }
    if (!isset($form['#library']['weight'])) {
      $form['#library']['weight'] = 5;
    }
    $form['library_type'] = array(
      '#type' => 'radios',
      '#title' => t('Source'),
      '#required' => TRUE,
      '#options' => array('external' => t('External URL'), 'file' => t('File')),
      '#default_value' => isset($library['type']) ? $library['type'] : 'external',
      '#disabled' => isset($library['type']),
    );
    $external_access = empty($library['type']) || $library['type'] == 'external';
    $form['external_url'] = array(
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => t('Enter the full URL of a JavaScript library. URL must start with http:// or https:// and end with .js or .txt.'),
      '#states' => array(
        'visible' => array(
          ':input[name="library_type"]' => array('value' => 'external'),
        ),
      ),
      '#default_value' => isset($library['uri']) ? $library['uri'] : '',
      '#access' => $external_access,
    );
    $form['cache_external'] = array(
      '#type' => 'checkbox',
      '#title' => t('Cache script locally'),
      '#description' => t('This option only takes effect if JavaScript aggregation is enabled. You must verify that the license or terms of service for the script permit local caching and aggregation.'),
      '#default_value' => isset($library['cache']) ? $library['cache'] : FALSE,
      '#access' => $external_access,
      '#states' => array(
        'visible' => array(
          ':input[name="library_type"]' => array('value' => 'external'),
        ),
      ),
    );
    $form['js_file_upload'] = array(
      '#type' => 'managed_file',
      '#title' => t('File'),
      '#description' => t('Upload a JavaScript file from your computer. It must end in .js or .txt. It will be renamed to have a .txt extension.'),
      '#upload_location' => 'public://javascript_libraries',
      '#upload_validators' => array('file_validate_extensions' => array(0 => 'js')),
      '#default_value' =>  array($library['fid']),
      '#access' => empty($library['type']) || $library['type'] == 'file',
    );
    $form['scope'] = array(
      '#type' => 'select',
      '#title' => t('Region on page'),
      '#required' => TRUE,
      '#description' => t('Please note that footer is a recommended scope, unless specific requirements header region should not be used'),
      '#options' => array(
        'header' => t('Head'),
        'footer' => t('Footer'),
        'disabled' => '<' . t('Disabled') . '>'
      ),
      '#default_value' => isset($library['scope']) ? $library['scope'] : 'footer',
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Library description'),
      '#default_value' => isset($library['name']) ? $library['name'] : '',
      '#description' => 'Defaults to the file name or URL.',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#href' => 'admin/config/system/javascript-libraries/custom',
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state->getValue('library_type')) {
      case 'external':
        if (strlen($form_state->getValue('external_url')) == 0) {
          $form_state->setErrorByName('external_url', $this->t("An empty URL is invalid."));
        }
        elseif (!javascript_libraries_valid_external_url($form_state->getValue('external_url'))) {
          $form_state->setErrorByName('external_url', $this->t('This URL does not start with http:// or does not end with ".js" or ".txt".'));
        }
        break;
      case 'file':
        if (empty($form_state->getValue('js_file_upload'))) {
          $form_state->setErrorByName('js_file_upload', $this->t("File field is required when adding a file."));
        }
        break;
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $custom = \Drupal::config('javascript_libraries.settings')
      ->get('javascript_libraries_custom_libraries');
    switch ($form_state->getValue('library_type')) {
      case 'external':
        $id = 'ext-' . db_next_id();
        $lib_id = $form_state->getValue('lib_id');
        if (isset($lib_id) && !empty($lib_id)) {
          $id = $lib_id;
        }
        $custom[$id] = array(
          'id' => $id,
          'type' => 'external',
          'scope' => $form_state->getValue('scope'),
          'name' => $form_state->getValue('name'),
          'weight' => $form_state->getValue('weight'),
          'uri' => $form_state->getValue('external_url'),
          'cache' => $form_state->getValue('cache_external'),
        );
        break;
      case 'file':
        $fid = $form_state->getValue(array('js_file_upload', 0));
        $file = \Drupal\file\Entity\File::load($fid);
        $id = 'file-' . $fid;
        $lib_id = $form_state->getValue('lib_id');
        if (isset($lib_id) && !empty($lib_id)) {
          $id = $lib_id;
        }
        $custom[$id] = array(
          'type' => 'file',
          'scope' => $form_state->getValue('scope'),
          'weight' => $form_state->getValue('weight'),
          'id' => $id,
          'name' => $form_state->getValue('name'),
          'fid' => $fid,
          'uri' => $file->getFileUri(),
        );
        break;
    }
    \Drupal::configFactory()->getEditable('javascript_libraries.settings')
      ->set('javascript_libraries_custom_libraries', $custom)
      ->save();
    drupal_set_message('Your library has been saved . Please configure the region and weight.');
    $form_state->setRedirect('javascript_libraries.custom_form');
  }
}
