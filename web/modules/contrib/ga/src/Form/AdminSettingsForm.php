<?php

namespace Drupal\ga\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for editing Google Analytics settings.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ga_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ga.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ga.settings');

    $form['add_default_commands'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add default analytics commands'),
      '#description' => $this->t('Disable if another module will be providing these commands.'),
      '#default_value' => $config->get('add_default_commands'),
    ];

    $form['premium'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Premium Account'),
      '#description' => $this->t('Premium Google Analytics accounts have additional configuration options available'),
      '#default_value' => $config->get('premium'),
    ];

    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'basics',
      '#states' => [
        'visible' => [
          ':input[data-drupal-selector="edit-add-default-commands"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['basics'] = [
      '#type' => 'details',
      '#title' => $this->t('Basics'),
      '#group' => 'settings',
    ];
    $form['basics']['tracking_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Web Property Tracking ID'),
      '#description' => $this->t('Tracking ID in the format "UA-xxxxxxx-y"'),
      '#placeholder' => 'UA-xxxxxxxx-y',
      '#maxlength' => 20,
      '#size' => '20',
      '#default_value' => $config->get('tracking_id'),
    ];
    $form['basics']['sample_rate'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 100,
      '#title' => $this->t('Sample Rate'),
      '#description' => $this->t('Specify what percentage of users should be tracked. This defaults to 100 (no users are sampled out) but large sites may need to use a lower sample rate to stay within Google Analytics processing limits.'),
      '#default_value' => $config->get('sample_rate'),
    ];
    $form['basics']['site_speed_sample_rate'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 10,
      '#title' => $this->t('Site Speed Sample Rate'),
      '#description' => $this->t('This setting determines how often site speed tracking beacons will be sent. By default, 1% of users will automatically be tracked.'),
      '#default_value' => $config->get('site_speed_sample_rate'),
    ];
    $form['basics']['force_ssl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force SSL'),
      '#description' => $this->t('By default, tracking beacons sent from https pages will be sent using https while beacons sent from http pages will be sent using http. Enabling this option will force http pages to also send all beacons using https.'),
      '#default_value' => $config->get('force_ssl'),
    ];
    $form['basics']['send_pageview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send Pageview Event'),
      '#default_value' => $config->get('send_pageview'),
    ];

    $form['plugins'] = [
      '#type' => 'details',
      '#title' => $this->t('Plugins'),
      '#group' => 'settings',
      '#tree' => TRUE,
    ];
    $form['plugins']['linkid'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enhanced Link Attribution'),
      '#description' => $this->t('Improve the accuracy of your In-Page Analytics report by automatically differentiating between multiple links to the same URL on a single page by using link element IDs (<a href=":url">Documentation</a>).', [
        ':url' => 'https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-link-attribution',
      ]),
      '#default_value' => $config->get('plugins.linkid'),
    ];
    $form['plugins']['displayfeatures'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Features'),
      '#description' => $this->t('Enable Advertising Features in Google Analytics, such as Remarketing, Demographics and Interest Reporting, and more (<a href=":url">Documentation</a>).<br/> This option can also be enabled through your property settings.', [
        ':url' => 'https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-link-attribution',
      ]),
      '#default_value' => $config->get('plugins.displayfeatures'),
    ];
    $form['plugins']['linker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Linker'),
      '#description' => $this->t('Enable cross-domain tracking (<a href=":url">Documentation</a>).', [
        ':url' => 'https://developers.google.com/analytics/devguides/collection/analyticsjs/linker',
      ]),
      '#default_value' => $config->get('plugins.linker.enable'),
    ];
    $form['plugins']['linker_domains'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Linker Domains'),
      '#description' => $this->t('A comma separated list of domains.'),
      '#default_value' => implode(', ', $config->get('plugins.linker.domains')),
      '#states' => [
        'visible' => [
          ':input[data-drupal-selector="edit-plugins-linker"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['users'] = [
      '#type' => 'details',
      '#title' => $this->t('Users'),
      '#group' => 'settings',
    ];
    $form['users']['track_user_id'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track User ID'),
      '#description' => $this->t('Enable the analysis of groups of sessions, across devices (<a href=":url">Documentation</a>).', [
        ':url' => 'https://developers.google.com/analytics/devguides/collection/analyticsjs/cookies-user-id#user_id',
      ]),
      '#default_value' => $config->get('track_user_id'),
    ];

    $form['users']['user_roles'] = [
      '#type' => 'fieldset',
      '#title' => 'Restrict analytics by user role',
      '#tree' => TRUE,
    ];
    $form['users']['user_roles']['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Filter mode'),
      '#options' => [
        'disabled' => $this->t('Disabled'),
        'include' => $this->t('Include'),
        'exclude' => $this->t('Exclude'),
      ],
      '#default_value' => $config->get('user_roles.mode'),
    ];

    $rolesConfigKeys = $this->configFactory()->listAll('user.role.');
    $rolesConfig = $this->configFactory()->loadMultiple($rolesConfigKeys);
    usort($rolesConfig, function ($a, $b) {
      return $a->get('weight') - $b->get('weight');
    });
    $roleOptions = array_reduce($rolesConfig, function ($result, $item) {
      $result[$item->get('id')] = $item->get('label');
      return $result;
    }, []);
    $form['users']['user_roles']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => $roleOptions,
      '#default_value' => $config->get('user_roles.roles'),
    ];

    $form['privacy'] = [
      '#type' => 'details',
      '#title' => $this->t('Privacy'),
      '#group' => 'settings',
    ];
    $form['privacy']['anonymize_ip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('IP Anonymization'),
      '#description' => $this->t('Request IP address anonymization (<a href=":url">Documentation</a>).', [
        ':url' => 'https://developers.google.com/analytics/devguides/collection/analyticsjs/ip-anonymization',
      ]),
      '#default_value' => $config->get('anonymize_ip'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $property = $form_state->getValue('tracking_id');
    if (!empty($property) && !preg_match('/^UA-\d+-\d+$/', $property)) {
      $form_state->setErrorByName('tracking_id', $this->t('The provided Tracking ID is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('ga.settings')
      ->set('add_default_commands', $form_state->getValue('add_default_commands'))
      ->set('premium', $form_state->getValue('premium'))
      ->set('tracking_id', $form_state->getValue('tracking_id'))
      ->set('sample_rate', $form_state->getValue('sample_rate'))
      ->set('site_speed_sample_rate', $form_state->getValue('site_speed_sample_rate'))
      ->set('force_ssl', $form_state->getValue('force_ssl'))
      ->set('send_pageview', $form_state->getValue('send_pageview'))
      ->set('plugins.linkid', $form_state->getValue(['plugins', 'linkid']))
      ->set('plugins.displayfeatures', $form_state->getValue(['plugins', 'displayfeatures']))
      ->set('plugins.linker.enable', $form_state->getValue(['plugins', 'linker']))
      ->set('plugins.linker.domains', array_filter(preg_split('/, ?/', $form_state->getValue(['plugins', 'linker_domains']))))
      ->set('track_user_id', $form_state->getValue('track_user_id'))
      ->set('user_roles.mode', $form_state->getValue(['user_roles', 'mode']))
      ->set('user_roles.roles', array_values(array_filter($form_state->getValue(['user_roles', 'roles']))))
      ->set('anonymize_ip', $form_state->getValue('anonymize_ip'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
