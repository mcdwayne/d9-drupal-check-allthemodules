<?php

namespace Drupal\google_calendar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_calendar\GoogleCalendarSecretsException;
use Drupal\user\Entity\User;
use Drupal\file\Entity\File;
use Drupal\google_calendar\GoogleCalendarSecretsFileInterface;
use Drupal\google_calendar\GoogleCalendarSecretsManagedFile;
use Drupal\google_calendar\GoogleCalendarSecretsStaticFile;

/**
 * Class TestSettingsForm.
 *
 * @ingroup test
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'google_calendar_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('google_calendar.default');

    $type = $form_state->getValue('client_secret_type');
    if ($type == 'managed') {
      // Make sure the file is marked permanent, as there's no known user.
      $secret = $form_state->getValue('client_secret');
      $file = File::load($secret[0]);
      $file->setPermanent();
      $file->save();
      $config
        ->set('secret_file_id', $file->id());
    }

    //Save to settings
    $config
      ->set('client_secret_type', $type)
      ->set(GoogleCalendarSecretsStaticFile::CONFIG_SECRET_FILE_NAME, $form_state->getValue('client_secret_static'))
      ->set(GoogleCalendarSecretsManagedFile::CONFIG_SECRET_FILE_ID, $form_state->getValue('client_secret_managed'))
      ->set('entity_ownership', $form_state->getValue('entity_ownership'))
      ->set('default_event_owner', $form_state->getValue('default_event_owner'))
      ->save();
  }

  /**
   * Defines the settings form for Test entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_calendar.default');

    $entity_ownership = $config->get('entity_ownership');
    $form['ownership'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Event ownership'),
    ];
    $form['ownership']['entity_ownership'] = [
      '#type' => 'container',
      '#title' => $this->t('Which User owns imported calendar events'),
      '#prefix' => $this->t(
        'Events imported from Google Calendar have a name and an email address, '
        . 'but Drupal\'s access control system requires a site User ID linked to each. '
        . 'This setting only affects events imported or updated from now on; older '
        . 'events are left as-is.'
      ),
      '#required' => TRUE,
    ];
    $form['ownership']['entity_ownership']['fixed']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('Always set to the default.'),
      '#return_value' => 'fixed',
      '#parents' => array('entity_ownership'),
      '#default_value' => $entity_ownership == 'fixed' ?? NULL,
    ];
    $form['ownership']['entity_ownership']['by_email']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('Derive from the Calendar event organizer\'s email address, otherwise use default.'),
      '#return_value' => 'by_email',
      '#parents' => array('entity_ownership'),
      '#default_value' => $entity_ownership == 'by_email' ?? NULL,
    ];
    $form['ownership']['entity_ownership']['by_name']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('Derive from the Calendar event organizer\'s name, otherwise use default.'),
      '#return_value' => 'by_name',
      '#parents' => array('entity_ownership'),
      '#default_value' => $entity_ownership == 'by_name' ?? NULL,
    ];

    $form['ownership']['default_event_owner'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default imported event owner'),
      '#description' => $this->t('The event owner set for Fixed ownership, or when the name or email of the event does not match a drupal user account.'),
      '#target_type' => 'user',
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'include_anonymous' => FALSE
      ],
      '#process_default_value' => FALSE,
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#default_value' => '',
    ];
    $default_event_owner = $config->get('default_event_owner');
    if ($default_event_owner !== NULL && $user = User::load($default_event_owner)) {
      $form['ownership']['default_event_owner']['#default_value'] =
        ['target_id' => $user->getAccountName() . ' (' . $user->id() . ')'];
    }

    $form['accounts'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Account details'),
    ];

    $service = \Drupal::service('google_calendar.secrets_file');
    $file_id = NULL;
    $file_content = NULL;
    if ($service instanceof GoogleCalendarSecretsManagedFile) {
      /** @var GoogleCalendarSecretsManagedFile $service */
      $form['accounts']['client_secret_type'] = [
        '#type' => 'hidden',
        '#value' => 'managed',
      ];
      $form['accounts']['client_secret_managed'] = [
        '#type' => 'managed_file',
        '#title' => t('Upload Client Secret File'),
        '#upload_location' => 'private://google-calendar/',
        '#default_value' => "",
        '#description' => t('Client Secret JSON file.'),
        '#upload_validators' => [
          'file_validate_extensions' => ['json']
        ],
      ];
      $fileid = $config->get('secret_file_id');
      if ($fileid !== NULL && $file = File::load($fileid)) {
        $form['client_secret']['#default_value'] = ['target_id' => $file->id()];
      }
      try {
        $file_content = $service->get();
      }
      catch (GoogleCalendarSecretsException $ex) {
        $file_content = '';
      }
    }

    elseif ($service instanceof GoogleCalendarSecretsStaticFile) {
      /** @var GoogleCalendarSecretsStaticFile $service */
      $file_id = $service->getFilePath();
      $form['accounts']['client_secret_type'] = [
        '#type' => 'hidden',
        '#value' => 'static',
      ];
      $form['accounts']['client_secret_static'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Client Secret File path'),
        '#default_value' => $file_id,
        '#maxlength' => 255,
        '#size' => 50,
        '#description' => t('Server path to the file, either relative to Drupal root or absolute.'),
        '#required' => TRUE,
      ];
      try {
        $file_content = $service->get();
      }
      catch (GoogleCalendarSecretsException $ex) {
        $file_content = '';
      }
    }

    elseif ($service instanceof GoogleCalendarSecretsFileInterface) {
      $form['accounts']['client_secret_unknown'] = [
        '#type' => 'markup',
        '#markup' => $this->t('The secrets_file service cannot be configured using this form.'),
      ];
    }

    if (!empty($file_content) && isset($file_content['type'])) {
      $type = $file_content['type'] == 'service_account' ? t('Service Account') : $file_content['type'];
      $mgmturl = Url::fromUri(
        'https://console.developers.google.com/iam-admin/serviceaccounts?project=' . $file_content['project_id']
      );
      $mgmturl_markup = Link::fromTextAndUrl('console.developers.google.com/iam-admin/...', $mgmturl);
      $markup = [
        '#type' => 'table',
        '#id' => 'account-information',
        '#attributes' => ['class' => ['account-info']],
        '#no_striping' => TRUE,
        '#rows' => [
          [
            'data' => [
              'label' => [
                'data' => [
                  '#markup' => $this->t('File Path:'),
                ],
              ],
              'value' => [
                'data' => [
                  '#markup' => $file_id,
                ],
              ],
            ]
          ],
          [
            'data' => [
              'label' => [
                'data' => [
                  '#markup' => $this->t('Account Type:'),
                ],
              ],
              'value' => [
                'data' => [
                  '#markup' => $type,
                ],
              ],
            ]
          ],
          [
            'data' => [
              'label' => [
                'data' => [
                  '#markup' => $this->t('Account Email:'),
                ],
              ],
              'value' => [
                'data' => [
                  '#markup' => $file_content['client_email'],
                ],
              ],
            ],
          ],
          [
            'data' => [
              'label' => [
                'data' => [
                  '#markup' => $this->t('Account Key ID:'),
                ],
              ],
              'value' => [
                'data' => [
                  '#markup' => $file_content['private_key_id'],
                ],
              ],
            ],
          ],
          [
            'data' => [
              'label' => [
                'data' => [
                  '#markup' => $this->t('Google Account Management:'),
                ],
              ],
              'value' => [
                'data' => $mgmturl_markup,
              ],
            ],
          ],
        ],
      ];
    }
    else {
      $markup = [
        '#type' => 'markup',
        '#markup' => $this->t('File "@path" was not found, or was invalid.', ['@path' => $file_id]),
      ];
    }

    $form['accounts']['account_info'] = $markup;

    return parent::buildForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['google_calendar.default'];
  }
}
