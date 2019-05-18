<?php

/**
 * @file
 * Contains \Drupal\javascript_libraries\Form\JavascriptLibrariesEditForm.
 */

namespace Drupal\javascript_libraries\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class JavascriptLibrariesEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'javascript_libraries_edit_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $library = NULL) {

    $form['#library'] = $library;
    if (!isset($form['#library']['weight'])) {
      $form['#library']['weight'] = 5;
    }

    $form['library_type'] = [
      '#type' => 'radios',
      '#title' => t('Source'),
      '#required' => TRUE,
      '#options' => [
        'external' => t('External URL'),
        'file' => t('File'),
      ],
      '#default_value' => isset($library['type']) ? $library['type'] : 'external',
      '#disabled' => isset($library['type']),
    ];

    $external_access = empty($library['type']) || $library['type'] == 'external';
    $form['external_url'] = [
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => t('Enter the full URL of a JavaScript library. URL must start with http:// or https:// and end with .js or .txt.'),
      '#states' => [
        'visible' => [
          ':input[name="library_type"]' => [
            'value' => 'external'
          ]
        ]
      ],
      '#default_value' => isset($library['uri']) ? $library['uri'] : '',
      '#access' => $external_access,
    ];


    $form['cache_external'] = [
      '#type' => 'checkbox',
      '#title' => t('Cache script locally'),
      '#description' => t('This option only takes effect if JavaScript aggregation is enabled. You must verify that the license or terms of service for the script permit local caching and aggregation.'),
      '#default_value' => isset($library['cache']) ? $library['cache'] : FALSE,
      '#access' => $external_access,
      '#states' => [
        'visible' => [
          ':input[name="library_type"]' => [
            'value' => 'external'
          ]
        ]
      ],
    ];

    $file_access = empty($library['type']) || $library['type'] == 'file';
    $form['js_file_upload'] = [
      '#type' => 'managed_file',
      '#title' => t('File'),
      '#description' => t('Upload a JavaScript file from your computer. It must end in .js or .txt. It will be renamed to have a .txt extension.'),
      '#upload_location' => 'public://javascript_libraries',
      '#upload_validators' => [
        'file_validate_extensions' => [
          0 => 'js txt'
        ]
      ],
      '#states' => [
        'visible' => [
          ':input[name="library_type"]' => [
            'value' => 'file'
          ]
        ]
      ],
      '#default_value' => $file_access && isset($library['fid']) ? $library['fid'] : NULL,
      '#access' => empty($library['type']) || $library['type'] == 'file',
    ];

    $form['scope'] = [
      '#type' => 'select',
      '#title' => t('Region on page'),
      '#required' => TRUE,
      '#options' => [
        'header' => t('Head'),
        'footer' => t('Footer'),
        'disabled' => '<' . t('Disabled') . '>',
      ],
      '#default_value' => isset($library['scope']) ? $library['scope'] : 'footer',
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Library description'),
      '#default_value' => isset($library['name']) ? $library['name'] : '',
      '#description' => 'Defaults to the file name or URL.',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#href' => 'admin/config/system/javascript-libraries/custom',
    ];

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    switch ($form_state->getValue(['library_type'])) {
      case 'external':
        _javascript_libraries_url_validate($form, $form_state);
        break;
      case 'file':
        _javascript_libraries_file_validate($form, $form_state);
        break;
    }
    // Trim the name/description:
    $form_state->setValue(['name'], trim($form_state->getValue([
      'name'
    ])));
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    switch ($form_state->getValue(['library_type'])) {
      case 'external':
        if (empty($form['#library']['id'])) {
          // New URL
          $form['#library']['id'] = 'ext-' . db_next_id();
        }
        // @FIXME
        // Could not extract the default value because it is either indeterminate, or
        // not scalar. You'll need to provide a default value in
        // config/install/javascript_libraries.settings.yml and config/schema/javascript_libraries.schema.yml.
        $custom = \Drupal::config('javascript_libraries.settings')
          ->get('javascript_libraries_custom_libraries');
        if (strlen($form_state->getValue(['name'])) == 0) {
          $parts = explode('/', $form_state->getValue(['external_url']));
          $form_state->setValue(['name'], '... /' . end($parts));
        }
        $custom[$form['#library']['id']] = [
          'id' => $form['#library']['id'],
          'type' => 'external',
          'scope' => $form_state->getValue(['scope']),
          'name' => $form_state->getValue(['name']),
          'weight' => $form['#library']['weight'],
          'uri' => $form_state->getValue(['external_url']),
          'cache' => $form_state->getValue(['cache_external']),
        ];
        \Drupal::configFactory()
          ->getEditable('javascript_libraries.settings')
          ->set('javascript_libraries_custom_libraries', $custom)
          ->save();
        break;
      case 'file':
        _javascript_libraries_file_submit($form, $form_state);
        // Change query-strings on css/js files to enforce reload for all users.
        javascript_libraries_js_cache_clear();
        break;
    }
    drupal_set_message('Your library has been added. Please configure the region and weight.');
    $form_state->set(['redirect'], 'admin/config/system/javascript-libraries/custom');
  }

}
