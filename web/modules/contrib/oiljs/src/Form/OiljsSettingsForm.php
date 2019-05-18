<?php

namespace Drupal\oiljs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 *
 */
class OiljsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oiljs_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['oiljs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('oiljs.settings');

    $form['oiljs_settings']['setup'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Setup'),
    ];
    $form['oiljs_settings']['setup']['script_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Script URL'),
      '#description' => $this->t('Enter the URL to the version of oil.js you plan to use.'),
      '#default_value' => $config->get('script_url'),
      '#required' => TRUE,
    ];
    $form['oiljs_settings']['setup']['exclude_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude paths'),
      '#default_value' => !empty($config->get('exclude_paths')) ? $config->get('exclude_paths') : '',
      '#description' => $this->t("Specify pages by using their system paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
        '%blog' => '/blog',
        '%blog-wildcard' => '/blog/*',
        '%front' => '<front>',
      ]),
    ];
    $form['oiljs_settings']['setup']['exclude_admin_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude admin pages'),
      '#description' => $this->t('If checked, the banner will not be displayed on all pages using the admin theme.'),
      '#default_value' => $config->get('exclude_admin_theme'),
    ];

    $form['oiljs_settings']['configuration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration'),
    ];
    $form['oiljs_settings']['configuration']['locale'] = [
      '#title' => $this->t('Locale'),
      '#type' => 'select',
      '#options' => [
        'deDE_01' => $this->t('German'),
        'enEN_01' => $this->t('English'),
        'plPL_01' => $this->t('Polish'),
      ],
      '#description' => $this->t('The locale version that should be used. The locale defines the standard labels for all legal texts and buttons.'),
      '#default_value' => $config->get('locale'),
    ];
    $form['oiljs_settings']['configuration']['theme'] = [
      '#title' => $this->t('Theme'),
      '#type' => 'select',
      '#options' => [
        'light' => $this->t('Light'),
        'small light' => $this->t('Small light'),
        'dark' => $this->t('Dark'),
        'small dark' => $this->t('Small dark'),
      ],
      '#description' => $this->t('The theme for the layer.'),
      '#default_value' => $config->get('theme'),
    ];
    $form['oiljs_settings']['configuration']['advanced_settings'] = [
      '#title' => $this->t('Advanced settings'),
      '#type' => 'checkbox',
      '#description' => $this->t('Replaces the No Button with a advanced settings button, which enables the user to select between different settings of privacy. The results of this selection is stored in the oil cookie as well.'),
      '#default_value' => $config->get('advanced_settings'),
    ];
    $form['oiljs_settings']['configuration']['preview_mode'] = [
      '#title' => $this->t('Preview mode'),
      '#type' => 'checkbox',
      '#description' => $this->t('The preview mode is useful when testing OIL in a production or live environment. As a dev you can trigger the overlay by setting a cookie named "oil_preview" with the value "true". This will show the OIL layer on your client.'),
      '#default_value' => $config->get('preview_mode'),
    ];
    $form['oiljs_settings']['configuration']['persist_min_tracking'] = [
      '#title' => $this->t('Persist minimum tracking'),
      '#type' => 'checkbox',
      '#description' => $this->t('If minimum tracking should result in removing all OIL cookies from the users browser and close the layer and store this selection in the oil cookie.'),
      '#default_value' => $config->get('persist_min_tracking'),
    ];
    $form['oiljs_settings']['configuration']['default_to_optin'] = [
      '#title' => $this->t('Default to opt-in'),
      '#type' => 'checkbox',
      '#description' => $this->t('Signal opt-in to vendors while still displaying the Opt-In layer to the end user.'),
      '#default_value' => $config->get('default_to_optin'),
    ];
    $form['oiljs_settings']['configuration']['cookie_expires_in_days'] = [
      '#title' => $this->t('Cookie expiration'),
      '#type' => 'number',
      '#min' => 1,
      '#description' => $this->t('Value in days until the domain cookie used to save the users decision in days.'),
      '#default_value' => $config->get('cookie_expires_in_days'),
    ];
    $form['oiljs_settings']['configuration']['timeout'] = [
      '#title' => $this->t('Timeout'),
      '#type' => 'number',
      '#min' => 0,
      '#description' => $this->t('Value in seconds until the opt-in layer will be automatically hidden. 0 deactivates auto-hide.'),
      '#default_value' => $config->get('timeout'),
    ];

    $label_documentation = Link::fromTextAndUrl($this->t('label parameter documentation'), Url::fromUri('https://oil.axelspringer.com/docs/#label-configuration-parameters'))->toString();
    $form['oiljs_settings']['labels'] = [
      '#type' => 'details',
      '#title' => $this->t('Label overrides'),
      '#collapsible' => TRUE,
      '#description' => $this->t('For default values and more informations visit the @documentation.', ['@documentation' => $label_documentation])
    ];
    $form['oiljs_settings']['labels']['label_intro_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Intro heading'),
      '#default_value' => $config->get('label_intro_heading'),
    ];
    $form['oiljs_settings']['labels']['label_intro'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Intro'),
      '#default_value' => $config->get('label_intro'),
      '#rows' => 3,
    ];
    $form['oiljs_settings']['labels']['label_button_yes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button yes'),
      '#default_value' => $config->get('label_button_yes'),
    ];
    $form['oiljs_settings']['labels']['label_button_back'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button back'),
      '#default_value' => $config->get('label_button_back'),
    ];
    $form['oiljs_settings']['labels']['label_button_advanced_settings'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button advanced settings'),
      '#default_value' => $config->get('label_button_advanced_settings'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC heading'),
      '#default_value' => $config->get('label_cpc_heading'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC text'),
      '#default_value' => $config->get('label_cpc_text'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_activate_all'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC activate all'),
      '#default_value' => $config->get('label_cpc_activate_all'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_deactivate_all'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC deactivate all'),
      '#default_value' => $config->get('label_cpc_deactivate_all'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_desc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC purpose'),
      '#default_value' => $config->get('label_cpc_purpose_desc'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_01_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC purpose 01 text'),
      '#default_value' => $config->get('label_cpc_purpose_01_text'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_01_desc'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CPC purpose 01 description'),
      '#default_value' => $config->get('label_cpc_purpose_01_desc'),
      '#rows' => 2,
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_02_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC purpose 02 text'),
      '#default_value' => $config->get('label_cpc_purpose_02_text'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_02_desc'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CPC purpose 02 description'),
      '#default_value' => $config->get('label_cpc_purpose_02_desc'),
      '#rows' => 2,
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_03_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC purpose 03 text'),
      '#default_value' => $config->get('label_cpc_purpose_03_text'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_03_desc'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CPC purpose 03 description'),
      '#default_value' => $config->get('label_cpc_purpose_03_desc'),
      '#rows' => 2,
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_04_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC purpose 04 text'),
      '#default_value' => $config->get('label_cpc_purpose_04_text'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_04_desc'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CPC purpose 04 description'),
      '#default_value' => $config->get('label_cpc_purpose_04_desc'),
      '#rows' => 2,
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_05_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC purpose 05 text'),
      '#default_value' => $config->get('label_cpc_purpose_05_text'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_05_desc'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CPC purpose 05 description'),
      '#default_value' => $config->get('label_cpc_purpose_05_desc'),
      '#rows' => 2,
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_06_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC purpose 06 text'),
      '#default_value' => $config->get('label_cpc_purpose_06_text'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_06_desc'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CPC purpose 06 description'),
      '#default_value' => $config->get('label_cpc_purpose_06_desc'),
      '#rows' => 2,
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_07_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPC purpose 07 text'),
      '#default_value' => $config->get('label_cpc_purpose_07_text'),
    ];
    $form['oiljs_settings']['labels']['label_cpc_purpose_07_desc'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CPC purpose 07 description'),
      '#default_value' => $config->get('label_cpc_purpose_07_desc'),
      '#rows' => 2,
    ];
    $form['oiljs_settings']['labels']['label_poi_group_list_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group list heading'),
      '#default_value' => $config->get('label_poi_group_list_heading'),
    ];
    $form['oiljs_settings']['labels']['label_poi_group_list_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Group list text'),
      '#default_value' => $config->get('label_poi_group_list_text'),
      '#rows' => 2,
    ];
    $form['oiljs_settings']['labels']['label_third_party'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Third party'),
      '#default_value' => $config->get('label_third_party'),
    ];
    $form['oiljs_settings']['labels']['label_thirdparty_list_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Third parties list heading'),
      '#default_value' => $config->get('label_thirdparty_list_heading'),
    ];
    $form['oiljs_settings']['labels']['label_thirdparty_list_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Third parties list text'),
      '#default_value' => $config->get('label_thirdparty_list_text'),
      '#rows' => 5,
    ];
    $form['oiljs_settings']['labels']['label_nocookie_head'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No cookie heading'),
      '#default_value' => $config->get('label_nocookie_head'),
    ];
    $form['oiljs_settings']['labels']['label_nocookie_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('No cookie text'),
      '#default_value' => $config->get('label_nocookie_text'),
      '#rows' => 3,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('oiljs.settings');
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
