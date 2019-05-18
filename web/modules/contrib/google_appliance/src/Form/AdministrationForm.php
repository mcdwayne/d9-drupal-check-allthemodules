<?php

namespace Drupal\google_appliance\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class AdministrationForm.
 *
 * @package Drupal\google_appliance\Form
 */
class AdministrationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_appliance_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return 'google_appliance.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $settings = $this->configFactory()->get($this->getEditableConfigNames())->get();
    $settings['display_settings']['onebox_modules'] = implode("\n", $settings['display_settings']['onebox_modules']);

    $form['connection_info'] = [
      '#title' => $this->t('Connection Information'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['connection_info']['hostname'] = [
      '#type' => 'url',
      '#title' => $this->t('Google Search Appliance Host Name'),
      '#description' => $this->t('Valid URL or IP address of the GSA device, including <em>http://</em> or <em>https://</em>. Do <b>not</b> include <em>/search</em> at the end, or a trailing slash, but you should include a port number if needed. Example: <em>http://my.gsabox.com:8443</em>'),
      '#default_value' => $settings['connection_info']['hostname'],
      '#required' => TRUE,
    ];
    $form['connection_info']['collection'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection'),
      '#description' => $this->t('The name of a valid collection on the GSA device (case sensitive).'),
      '#default_value' => $settings['connection_info']['collection'],
      '#required' => TRUE,
    ];
    $form['connection_info']['frontend'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontend client'),
      '#description' => $this->t('The name of a valid frontend client on the GSA device (case sensitive).'),
      '#default_value' => $settings['connection_info']['frontend'],
      '#required' => TRUE,
    ];
    $form['connection_info']['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Search Timeout'),
      '#default_value' => $settings['connection_info']['timeout'],
      '#min' => 3,
      '#max' => 30,
      '#description' => $this->t('Length of time to wait for response from the GSA device before giving up (timeout in seconds).'),
      '#required' => TRUE,
    ];

    $form['query_param'] = [
      '#title' => $this->t('Search Query Parameter Setup'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['query_param']['autofilter'] = [
      '#type' => 'select',
      '#title' => $this->t('Search Results Auto-Filtering Options'),
      '#default_value' => $settings['query_param']['autofilter'],
      '#options' => [
        '0' => $this->t('No filtering'),
        's' => $this->t('Duplicate Directory Filter'),
        'p' => $this->t('Duplicate Snippet Filter'),
        '1' => $this->t('Both Duplicate Directory and Duplicate Snippet Filters'),
      ],
      '#description' => $this->t('Learn more about GSA auto-filtering @gsa_link. In general, employing both filters enhances results, but sites with smaller indexes may suffer from over-filtered results.',
        [
          '@gsa_link' => Link::fromTextAndUrl(
            $this->t('here'),
            Url::fromUri('http://code.google.com/apis/searchappliance/documentation/68/xml_reference.html#request_filter_auto')
          )->toString(),
        ]
      ),
    ];
    // // @todo: FIX
    //    if (\Drupal::moduleHandler()->moduleExists('locale')) {
    //      $form['query_param']['language_filter_toggle'] = [
    //        '#type' => 'checkbox',
    //        '#title' => $this->t('Enable Language Filtering'),
    //        '#default_value' => $settings['query_param']['language_filter_toggle'],
    //      ];
    //      $form['query_param']['language_filter_options'] = [
    //        '#type' => 'checkboxes',
    //        '#title' => $this->t('Restrict searches to specified languages'),
    //        '#default_value' => $settings['query_param']['language_filter_options'],
    //        // @todo: FIX
    //        '#options' => [
    //            '***CURRENT_LANGUAGE***' => $this->t("Current user's language"),
    //            '***DEFAULT_LANGUAGE***' => $this->t('Default site language'),
    //          ] + locale_language_list(),
    //        '#states' => [
    //          'visible' => [
    //            ':input[name=language_filter_toggle]' => ['checked' => TRUE],
    //          ],
    //        ],
    //        '#description' => $this->t('If there are no results in the specified language, the search appliance is expected to return results in all languages.'),
    //      ];
    //    }.
    $form['query_param']['query_inspection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Query Inspection'),
      '#description' => $this->t('Inspect the search query parameters sent to the GSA device in the Drupal message area every time a search is performed. Only really useful for sites not using the Devel module, as dsm() provides more information. The inspector is only shown to administrators, but should be disabled in a production environment.'),
      '#default_value' => $settings['query_param']['query_inspection'],
    ];

    $form['display_settings'] = [
      '#title' => $this->t('Search Interface Settings'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['display_settings']['drupal_path'] = [
      '#title' => $this->t('Search path'),
      '#type' => 'textfield',
      '#field_prefix' => '<span dir="ltr">' . Url::fromUserInput('/', ['absolute' => TRUE])->toString(),
      '#field_suffix' => '</span>&lrm;',
      '#default_value' => $settings['display_settings']['drupal_path'],
      '#description' => $this->t('The URL of the search page provided by this module. Include neither leading nor trailing slash.'),
      '#required' => TRUE,
    ];
    $form['display_settings']['search_title'] = [
      '#title' => $this->t('Search Name'),
      '#type' => 'textfield',
      '#default_value' => $settings['display_settings']['search_title'],
      '#description' => $this->t('Serves as the page title on results pages and the default menu item title'),
      '#required' => TRUE,
    ];
    $form['display_settings']['results_per_page'] = [
      '#title' => $this->t('Results per page'),
      '#type' => 'number',
      '#default_value' => $settings['display_settings']['results_per_page'],
      '#min' => 1,
      '#max' => 1000,
      '#description' => $this->t('Number of results to show on the results page. More results will be available via a Drupal pager.'),
      '#required' => TRUE,
    ];
    $form['display_settings']['spelling_suggestions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Spelling Suggestions'),
      '#default_value' => $settings['display_settings']['spelling_suggestions'],
    ];
    $form['display_settings']['advanced_search_reporting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Advanced Search Reporting'),
      '#default_value' => $settings['display_settings']['advanced_search_reporting'],
      '#description' => $this->t('Learn more about @this_feature. You need to enable Advanced Search Reporting on the front end client. The device should provide a file named "/clicklog_compiled.js" when using the search interface on the GSA.',
        [
          '@this_feature' => Link::fromTextAndUrl(
            $this->t('this feature'),
            Url::fromUri('http://www.google.com/support/enterprise/static/gsa/docs/admin/70/gsa_doc_set/xml_reference/advanced_search_reporting.html')
          )->toString(),
        ]
      ),
    ];
    $form['display_settings']['sitelinks_search_box'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Google.com Sitelinks Search Box'),
      '#default_value' => $settings['display_settings']['sitelinks_search_box'],
      '#description' => $this->t(
        'Learn more about @this_feature. Note: your site may not necessarily be a candidate for the google.com Sitelinks search box.',
        [
          '@this_feature' => Link::fromTextAndUrl(
           $this->t('this feature'),
            Url::fromUri('https://developers.google.com/structured-data/slsb-overview')
          )->toString(),
        ]
      ),
    ];
    $form['display_settings']['onebox_modules'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Onebox modules'),
      '#description' => $this->t('A list of Onebox modules, one per line. Each module listed will have a corresponding block. These blocks must be placed via the block configuration page, or another layout mechanism like Context or Panels.'),
      '#default_value' => $settings['display_settings']['onebox_modules'],
    ];

    $form['display_settings']['error_gsa_no_results'] = [
      '#title' => $this->t('No results error message'),
      '#type' => 'text_format',
      '#default_value' => $settings['display_settings']['error_gsa_no_results']['value'],
      '#format' => $settings['display_settings']['error_gsa_no_results']['format'],
      '#description' => $this->t('The message displayed to the user when no results are found for the given search query.'),
      '#required' => TRUE,
    ];
    $form['display_settings']['error_curl_error'] = [
      '#title' => $this->t('Connection error message'),
      '#type' => 'text_format',
      '#default_value' => $settings['display_settings']['error_curl_error']['value'],
      '#format' => $settings['display_settings']['error_curl_error']['format'],
      '#description' => $this->t('The message displayed to the user when there is an error connecting to the search appliance.'),
      '#required' => TRUE,
    ];
    $form['display_settings']['error_lib_xml_parse_error'] = [
      '#title' => $this->t('XML parse error message'),
      '#type' => 'text_format',
      '#default_value' => $settings['display_settings']['error_lib_xml_parse_error']['value'],
      '#format' => $settings['display_settings']['error_lib_xml_parse_error']['format'],
      '#description' => $this->t('The message displayed to the user when the XML returned by the appliance is malformed.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $oneboxValue = explode("\n", $form_state->getValue('onebox_modules'));
    $oneboxValue = array_map('trim', $oneboxValue);
    $oneboxValue = array_filter($oneboxValue, 'strlen');

    $this->configFactory->getEditable($this->getEditableConfigNames())
      ->set('connection_info.hostname', $form_state->getValue('hostname'))
      ->set('connection_info.collection', $form_state->getValue('collection'))
      ->set('connection_info.frontend', $form_state->getValue('frontend'))
      ->set('connection_info.timeout', $form_state->getValue('timeout'))
      ->set('query_param.autofilter', $form_state->getValue('autofilter'))
      // @todo:
      //    'language_filter_toggle',
      //    'language_filter_options',
      ->set('query_param.query_inspection', $form_state->getValue('query_inspection'))
      ->set('display_settings.drupal_path', trim($form_state->getValue('drupal_path'), '/'))
      ->set('display_settings.search_title', $form_state->getValue('search_title'))
      ->set('display_settings.results_per_page', $form_state->getValue('results_per_page'))
      ->set('display_settings.spelling_suggestions', $form_state->getValue('spelling_suggestions'))
      ->set('display_settings.advanced_search_reporting', $form_state->getValue('advanced_search_reporting'))
      ->set('display_settings.sitelinks_search_box', $form_state->getValue('sitelinks_search_box'))
      ->set('display_settings.onebox_modules', $oneboxValue)
      // @todo:
      //    'block_visibility_settings',
      ->set('display_settings.error_gsa_no_results', $form_state->getValue('error_gsa_no_results'))
      ->set('display_settings.error_curl_error', $form_state->getValue('error_curl_error'))
      ->set('display_settings.error_lib_xml_parse_error', $form_state->getValue('error_lib_xml_parse_error'))
      ->save();

    \Drupal::service('router.builder')->rebuild();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // @todo
    //   For security, we check that the user has access to use these filters.
    //    $field_text_format_keys = [
    //      'error_gsa_no_results',
    //      'error_curl_error',
    //      'error_lib_xml_parse_error',
    //    ];
    //    $formats = filter_formats();
    //    foreach ($field_text_format_keys as $field) {
    //      if (!filter_access($formats[$form_state['values'][$field]['format']])) {
    //        form_set_error($field . '][format', $this->t('An illegal choice has been detected. Please contact the site administrator.'));
    //      }
    //      else {
    //        // Alter the formatted text area settings to our expectations.
    //        $form_state['values'][$field . '_format'] = $form_state['values'][$field]['format'];
    //        $form_state['values'][$field] = trim($form_state['values'][$field]['value']);
    //      }
    //    }
  }

}
