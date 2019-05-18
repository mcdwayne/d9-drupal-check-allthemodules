<?php

namespace Drupal\bu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Defines a form that configures browser-update.org settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bu_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bu.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get current settings.
    $bu_config = $this->config('bu.settings');

    $form['information'] = [
      '#type' => 'container',
      '#attributes' => [],
      '#children' => $this->t('These are the settings which determine how the browser-update.org update notification displays. You can test this notification by appending #test-bu to the end of any URL. For example, @homepageTest', [
        '@homepageTest' => Link::createFromRoute($this->t('click here to test the notification on the homepage'), '<front>', [], ['fragment' => 'test-bu'])->toString(),
      ]),
    ];

    // Set which versions receive the popup.
    $form['notify'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Browser Versions'),
      '#description' => $this->t('Choose which of the following browser versions will see the notification:'),
      'notify_ie' => [
        '#type' => 'select',
        '#title' => $this->t('IE/Edge'),
        '#options' => [
          '-0.01' => $this->t('Every outdated version'),
          '14' => $this->t('<= 14'),
          '13' => $this->t('<= 13'),
          '12' => $this->t('<= 12'),
          '11' => $this->t('<= 11'),
          '10' => $this->t('<= 10'),
          '9' => $this->t('<= 9'),
          '-6' => $this->t('more than 6 versions behind'),
          '-5' => $this->t('more than 5 versions behind'),
          '-4' => $this->t('more than 4 versions behind'),
          '-3' => $this->t('more than 3 versions behind'),
          '-2' => $this->t('more than 2 versions behind'),
          '-1' => $this->t('more than 1 versions behind'),
        ],
        '#default_value' => $bu_config->get('notify_ie'),
      ],
      'notify_firefox' => [
        '#type' => 'select',
        '#title' => $this->t('Firefox'),
        '#options' => [
          '-0.01' => $this->t('Every outdated version'),
          '56' => $this->t('<= 56'),
          '55' => $this->t('<= 55'),
          '54' => $this->t('<= 54'),
          '53' => $this->t('<= 53'),
          '52' => $this->t('<= 52'),
          '51' => $this->t('<= 51'),
          '-6' => $this->t('more than 6 versions behind'),
          '-5' => $this->t('more than 5 versions behind'),
          '-4' => $this->t('more than 4 versions behind'),
          '-3' => $this->t('more than 3 versions behind'),
          '-2' => $this->t('more than 2 versions behind'),
          '-1' => $this->t('more than 1 versions behind'),
        ],
        '#default_value' => $bu_config->get('notify_firefox'),
      ],
      'notify_opera' => [
        '#type' => 'select',
        '#title' => $this->t('Opera'),
        '#options' => [
          '-0.01' => $this->t('Every outdated version'),
          '49' => $this->t('<= 49'),
          '48' => $this->t('<= 48'),
          '47' => $this->t('<= 47'),
          '46' => $this->t('<= 46'),
          '45' => $this->t('<= 45'),
          '44' => $this->t('<= 44'),
          '-6' => $this->t('more than 6 versions behind'),
          '-5' => $this->t('more than 5 versions behind'),
          '-4' => $this->t('more than 4 versions behind'),
          '-3' => $this->t('more than 3 versions behind'),
          '-2' => $this->t('more than 2 versions behind'),
          '-1' => $this->t('more than 1 versions behind'),
        ],
        '#default_value' => $bu_config->get('notify_opera'),
      ],
      'notify_safari' => [
        '#type' => 'select',
        '#title' => $this->t('Safari'),
        '#options' => [
          '-0.01' => $this->t('Every outdated version'),
          '10' => $this->t('<= 10'),
          '9' => $this->t('<= 9'),
          '8' => $this->t('<= 8'),
          '7' => $this->t('<= 7'),
          '6' => $this->t('<= 6'),
          '5' => $this->t('<= 5'),
          '-6' => $this->t('more than 6 versions behind'),
          '-5' => $this->t('more than 5 versions behind'),
          '-4' => $this->t('more than 4 versions behind'),
          '-3' => $this->t('more than 3 versions behind'),
          '-2' => $this->t('more than 2 versions behind'),
          '-1' => $this->t('more than 1 versions behind'),
        ],
        '#default_value' => $bu_config->get('notify_safari'),
      ],
      'notify_chrome' => [
        '#type' => 'select',
        '#title' => $this->t('Chrome'),
        '#options' => [
          '-0.01' => $this->t('Every outdated version'),
          '62' => $this->t('<= 62'),
          '61' => $this->t('<= 61'),
          '60' => $this->t('<= 60'),
          '59' => $this->t('<= 59'),
          '58' => $this->t('<= 58'),
          '57' => $this->t('<= 57'),
          '-6' => $this->t('more than 6 versions behind'),
          '-5' => $this->t('more than 5 versions behind'),
          '-4' => $this->t('more than 4 versions behind'),
          '-3' => $this->t('more than 3 versions behind'),
          '-2' => $this->t('more than 2 versions behind'),
          '-1' => $this->t('more than 1 versions behind'),
        ],
        '#default_value' => $bu_config->get('notify_chrome'),
      ],
      'insecure' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Notify all browser versions with severe security issues.'),
        '#default_value' => $bu_config->get('insecure'),
      ],
      'unsupported' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Also notify all browsers that are not supported by the vendor anymore.'),
        '#default_value' => $bu_config->get('unsupported'),
      ],
      'mobile' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Notify mobile browsers.'),
        '#default_value' => $bu_config->get('mobile'),
      ],
    ];

    // Set visibility for the popup.
    $form['visibility'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Visibility'),
      '#description' => $this->t('Specify pages by using their paths. Enter one path per line. The "*" character is a wildcard. An example path is /user/* for every user page. <front> is the front page.'),
      'visibility_pages' => [
        '#type' => 'textarea',
        '#title' => $this->t('Pages'),
        '#default_value' => $bu_config->get('visibility_pages'),
      ],
      'visibility_type' => [
        '#type' => 'radios',
        '#options' => [
          'show' => $this->t('Show for the listed pages'),
          'hide' => $this->t('Hide for the listed pages'),
        ],
        '#default_value' => $bu_config->get('visibility_type'),
      ],
    ];

    // Additional settings.
    $form['additional'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Additional Settings'),
      'source' => [
        '#type' => 'url',
        '#title' => $this->t('Source File (base)'),
        '#description' => $this->t('This can be used to override the source file for the update script to point elsewhere. By default, the script is located at %url. Leave this setting blank to use the default.', [
          '%url' => '//browser-update.org/update.min.js',
        ]),
        '#default_value' => $bu_config->get('source'),
      ],
      'show_source' => [
        '#type' => 'url',
        '#title' => $this->t('Source File (display)'),
        '#description' => $this->t('This can be used to override the source file for the script which shows the notification. This is only loaded if the user actually has an outdated browser. By default, the script is located at %url. Leave this setting blank to use the default.', [
          '%url' => '//browser-update.org/update.show.min.js',
        ]),
        '#default_value' => $bu_config->get('show_source'),
      ],
      'position' => [
        '#type' => 'select',
        '#title' => $this->t('Position'),
        '#description' => $this->t('Set the display location of the message.'),
        '#options' => [
          'top' => $this->t('Top'),
          'bottom' => $this->t('Bottom'),
          'corner' => $this->t('Corner'),
        ],
        '#default_value' => $bu_config->get('position'),
      ],
      'text_override' => [
        '#type' => 'textarea',
        '#title' => $this->t('Message Text Override'),
        '#description' => $this->t('Placeholders can be used here. {brow_name} will be replaced with the browser name, {up_but} with contents of the update link tag and {ignore_but} with contents for the ignore link. Example:  %example.', [
          '%example' => 'Your browser, {brow_name}, is too old: <a{up_but}>update</a> or <a{ignore_but}>ignore</a>',
        ]),
        '#default_value' => $bu_config->get('text_override'),
      ],
      'reminder' => [
        '#type' => 'number',
        '#title' => $this->t('Reminder (in hours)'),
        '#description' => $this->t('Set after how many hours the message should reappear. A value of 0 means "show all the time".'),
        '#min' => 0,
        '#step' => 1,
        '#default_value' => $bu_config->get('reminder'),
      ],
      'reminder_closed' => [
        '#type' => 'number',
        '#title' => $this->t('Reminder after closing (in hours)'),
        '#description' => $this->t('Set after how many hours the message should reappear if the user explicity closes it.'),
        '#min' => 0,
        '#step' => 1,
        '#default_value' => $bu_config->get('reminder_closed'),
      ],
      'new_window' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Open link in new window'),
        '#description' => $this->t('Setting this checkbox will cause the browser update link to open in a new window.'),
        '#default_value' => $bu_config->get('new_window'),
      ],
      'url' => [
        '#type' => 'url',
        '#title' => $this->t('Destination URL'),
        '#description' => $this->t('Setting this will set the URL that the user is sent to when they click the notification.'),
        '#default_value' => $bu_config->get('url'),
      ],
      'no_close' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide Ignore Button'),
        '#description' => $this->t('Setting this checkbox will cause the "ignore" button to be hidden on the notification.'),
        '#default_value' => $bu_config->get('no_close'),
      ],
    ];

    // Test mode - display all the time.
    $form['test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test Mode'),
      '#description' => $this->t('Setting this checkbox will cause the message to be displayed all the time (for testing purposes).'),
      '#default_value' => $bu_config->get('test_mode'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save the updated settings.
    $this->config('bu.settings')
      ->set('notify_ie', $values['notify_ie'])
      ->set('notify_firefox', $values['notify_firefox'])
      ->set('notify_opera', $values['notify_opera'])
      ->set('notify_safari', $values['notify_safari'])
      ->set('notify_chrome', $values['notify_chrome'])
      ->set('insecure', $values['insecure'])
      ->set('unsupported', $values['unsupported'])
      ->set('mobile', $values['mobile'])
      ->set('position', $values['position'])
      ->set('visibility_pages', $values['visibility_pages'])
      ->set('visibility_type', $values['visibility_type'])
      ->set('text_override', $values['text_override'])
      ->set('reminder', $values['reminder'])
      ->set('reminder_closed', $values['reminder_closed'])
      ->set('test_mode', $values['test_mode'])
      ->set('new_window', $values['new_window'])
      ->set('url', $values['url'])
      ->set('no_close', $values['no_close'])
      ->set('source', $values['source'])
      ->set('show_source', $values['show_source'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
