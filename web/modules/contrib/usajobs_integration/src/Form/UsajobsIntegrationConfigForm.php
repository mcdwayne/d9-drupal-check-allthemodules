<?php

namespace Drupal\usajobs_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for administering usajobs search api settings.
 */
class UsajobsIntegrationConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usajobs_integration_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array(
      'usajobs_integration.settings',
      'usajobs_integration.parameters',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('usajobs_integration.settings');
    $params = $this->config('usajobs_integration.parameters');

    $form['api_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('USAjobs Search API Settings'),
      '#open' => TRUE,
    );
    $form['api_settings']['user_agent'] = [
      '#type' => 'email',
      '#title' => $this->t('User Agent'),
      '#placeholder' => 'user@agency.gov',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('user_agent'),
      '#description' => $this->t('Please enter the email address associated with an Authorization Key. See the <a href="https://developer.usajobs.gov/API-Reference" target="_blank">USAjobs documentation</a> for more information.'),
    ];
    $form['api_settings']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Please enter the API Key. Visit the USAjobs Search API to <a href="https://developer.usajobs.gov/APIRequest/Index" target="_blank">register for an API Key</a>.'),
    ];
    $form['api_settings']['endpoint_url'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('endpoint_url'),
    ];
    $form['api_settings']['host'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('host'),
    ];

    $form['filter_settings'] = array(
      '#type' => 'details',
      '#title' => 'USAjobs Search API Filters',
      '#description' => $this->t('Configure all parameters which will determine the results returned from the USAjobs Search API. <a href="https://developer.usajobs.gov/API-Reference/GET-JobSearch-Search" target="_blank">Read the USAjobs documentation</a> for complete information about these parameters.'),
    );
    $form['filter_settings']['Organization'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organizations'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => FALSE,
      '#default_value' => $params->get('Organization'),
      '#description' => $this->t('Find jobs for the specified agency using the Agency Subelement Code. <a href="https://data.usajobs.gov/api/codelist/agencysubelements" target="_blank">View all agency and subagency codes</a>. Multiple values are semicolon delimited.'),
    ];
    $form['filter_settings']['Keyword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => FALSE,
      '#default_value' => $params->get('Keyword'),
      '#description' => $this->t('Find jobs based on a keyword'),
    ];
    $form['filter_settings']['KeywordExclusion'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword Exclusion'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => FALSE,
      '#default_value' => $params->get('KeywordExclusion'),
      '#description' => $this->t('Find jobs NOT matching this value.'),
    ];
    $form['filter_settings']['KeywordFilter'] = [
      '#type' => 'select',
      '#title' => $this->t('Keyword Filter'),
      '#options' => [
        'All' => $this->t('All'),
        'Any' => $this->t('Any'),
        'Exact' => $this->t('Exact'),
      ],
      '#empty_option' => 'None',
      '#empty_value' => '',
      '#required' => FALSE,
      '#default_value' => $params->get('KeywordFilter'),
      '#description' => $this->t('Used with the Keyword, defines the type of phrase search to issue.'),
    ];
    $form['filter_settings']['RemunerationMinimumAmount'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum Salary'),
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => FALSE,
      '#default_value' => $params->get('RemunerationMinimumAmount'),
      '#description' => $this->t('Find jobs with the minimum salary specified.'),
    ];
    $form['filter_settings']['RemunerationMaximumAmount'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Salary'),
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => FALSE,
      '#default_value' => $params->get('RemunerationMaximumAmount'),
      '#description' => $this->t('Find jobs with the maximum salary specified.'),
    ];
    $form['filter_settings']['JobGradeCode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pay Plan'),
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => FALSE,
      '#default_value' => $params->get('JobGradeCode'),
      '#description' => $this->t('Find jobs of a certain pay plan. <em>(i.e. "GS")</em>'),
    ];
    $form['filter_settings']['PayGradeLow'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum Pay Grade'),
      '#size' => 30,
      '#maxlength' => 128,
      '#min' => 1,
      '#max' => 15,
      '#required' => FALSE,
      '#default_value' => $params->get('PayGradeLow'),
      '#description' => $this->t('Find jobs with the minimum pay grade specified.'),
    ];
    $form['filter_settings']['PayGradeHigh'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Pay Grade'),
      '#size' => 30,
      '#maxlength' => 128,
      '#min' => 1,
      '#max' => 15,
      '#required' => FALSE,
      '#default_value' => $params->get('PayGradeHigh'),
      '#description' => $this->t('Find jobs with the maximum pay grade specified.'),
    ];
    $form['filter_settings']['JobCategoryCode'] = [
      '#type' => 'number',
      '#title' => $this->t('Job Category (Series) Code'),
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => FALSE,
      '#default_value' => $params->get('JobCategoryCode'),
      '#description' => $this->t('Find jobs of a specific type using the category code. <a href="https://data.usajobs.gov/api/codelist/OccupationalSeries" target="_blank">View a list of all category codes</a>.'),
    ];
    $form['filter_settings']['LocationName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location Name'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => FALSE,
      '#default_value' => $params->get('LocationName'),
      '#description' => $this->t('Find jobs match a specific location. <em>(i.e. Washington,DC)</em> Multiple values are semicolon delimited'),
    ];
    $form['filter_settings']['Radius'] = [
      '#type' => 'number',
      '#title' => $this->t('Distance from Location'),
      '#size' => 30,
      '#maxlength' => 128,
      '#max' => 1000,
      '#required' => FALSE,
      '#default_value' => $params->get('Radius'),
      '#description' => $this->t('Used with Location Name to find jobs within a specific radius (in miles)'),
    ];
    $form['filter_settings']['PositionOfferingTypeCode'] = [
      '#type' => 'select',
      '#title' => $this->t('Work Type'),
      '#multiple' => TRUE,
      '#options' => [
        '15317' => $this->t('Permanent'),
        '15322' => $this->t('Seasonal'),
        '15327' => $this->t('Multiple Appointment Types'),
      ],
      '#empty_option' => 'Any',
      '#empty_value' => '',
      '#required' => FALSE,
      '#default_value' => $params->get('PositionOfferingTypeCode'),
      '#description' => $this->t('Find jobs by a specific work type.'),
    ];
    $form['filter_settings']['TravelPercentage'] = [
      '#type' => 'select',
      '#title' => $this->t('Travel Percentage'),
      '#options' => [
        '0' => $this->t('Not Required'),
        '1' => $this->t('Occasional Travel'),
        '2' => $this->t('25% or Greater'),
        '5' => $this->t('50% or Greater'),
        '7' => $this->t('75% or Greater'),
      ],
      '#empty_option' => 'Any',
      '#empty_value' => '',
      '#required' => FALSE,
      '#default_value' => $params->get('TravelPercentage'),
      '#description' => $this->t('Find jobs with a specific travel percent requirement.'),
    ];
    $form['filter_settings']['PositionScheduleTypeCode'] = [
      '#type' => 'select',
      '#title' => $this->t('Schedule Type'),
      '#multiple' => TRUE,
      '#options' => [
        '1' => $this->t('Full-Time'),
        '2' => $this->t('Part-Time'),
        '3' => $this->t('Shift Work'),
        '4' => $this->t('Intermittent'),
        '5' => $this->t('Job Sharing'),
        '6' => $this->t('Multiple Schedules'),
      ],
      '#empty_option' => 'Any',
      '#empty_value' => '',
      '#required' => FALSE,
      '#default_value' => $params->get('PositionScheduleTypeCode'),
      '#description' => $this->t('Find jobs with a specific work schedule.'),
    ];
    $form['filter_settings']['RelocationIndicator'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Relocation Authorized'),
      '#required' => FALSE,
      '#default_value' => $params->get('RelocationIndicator'),
      '#description' => $this->t('Only include jobs with relocation authorization.'),
    ];
    $form['filter_settings']['SecurityClearanceRequired'] = [
      '#type' => 'select',
      '#title' => $this->t('Security Clearance Required'),
      '#multiple' => TRUE,
      '#options' => [
        '0' => $this->t('Not Applicable'),
        '1' => $this->t('Confidential'),
        '2' => $this->t('Secret'),
        '3' => $this->t('Top Secret'),
        '4' => $this->t('Top Secret/SCI'),
        '5' => $this->t('Sensitive'),
        '6' => $this->t('Nonsensitive'),
        '7' => $this->t('Atomic Energy'),
        '8' => $this->t('Other'),
        '9' => $this->t('Public Trust'),
      ],
      '#required' => FALSE,
      '#default_value' => $params->get('SecurityClearanceRequired'),
      '#description' => $this->t('Find jobs with a specific security clearance.'),
    ];
    $form['filter_settings']['SupervisoryStatus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Supervisory Status'),
      '#required' => FALSE,
      '#default_value' => $params->get('SupervisoryStatus'),
      '#description' => $this->t('Only include supervisory positions.'),
    ];
    $form['filter_settings']['DatePosted'] = [
      '#type' => 'select',
      '#title' => $this->t('Posted Since'),
      '#options' => [
        '0' => $this->t('Today'),
        '1' => $this->t('Yesterday'),
        '3' => $this->t('In the last 3 days'),
        '7' => $this->t('In the last week'),
        '30' => $this->t('In the last month'),
        '60' => $this->t('In the last 2 months'),
      ],
      '#empty_option' => 'All Jobs',
      '#empty_value' => '-1',
      '#required' => FALSE,
      '#default_value' => $params->get('DatePosted'),
      '#description' => $this->t('Find jobs since a certain amount of time.'),
    ];
    $form['filter_settings']['WhoMayApply'] = [
      '#type' => 'select',
      '#title' => $this->t('Who may apply'),
      '#options' => [
        'public' => $this->t('Public'),
        'all' => $this->t('All'),
        'status' => $this->t('Status'),
      ],
      '#required' => FALSE,
      '#default_value' => $params->get('WhoMayApply'),
      '#description' => $this->t('Only include jobs with a specific application requirement. Note: <a href="https://developer.usajobs.gov/Guides/Rate-Limiting" target="blank">Rate limits</a> may apply.'),
    ];
    $form['filter_settings']['SES'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Senior Executive Service'),
      '#required' => FALSE,
      '#default_value' => $params->get('SES'),
      '#description' => $this->t('Only include Senior Executive Service positions.'),
    ];
    $form['filter_settings']['Student'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Student'),
      '#required' => FALSE,
      '#default_value' => $params->get('Student'),
      '#description' => $this->t('Only include Student positions.'),
    ];
    $form['filter_settings']['Internship'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Internship'),
      '#required' => FALSE,
      '#default_value' => $params->get('Internship'),
      '#description' => $this->t('Only include Intern positions.'),
    ];
    $form['filter_settings']['RecentGrad'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recent Graduate'),
      '#required' => FALSE,
      '#default_value' => $params->get('RecentGrad'),
      '#description' => $this->t('Only include Recent Graduate positions.'),
    ];
    $form['filter_settings']['ResultsPerPage'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Number of Results'),
      '#min' => 1,
      '#max' => 500,
      '#required' => FALSE,
      '#default_value' => $params->get('ResultsPerPage') ? $params->get('ResultsPerPage') : 10,
      '#description' => $this->t('The maximum number of Job Listings to display.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('usajobs_integration.settings')
      ->set('user_agent', $form_state->getValue('user_agent'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('endpoint', $form_state->getValue('host'))
      ->save();
    $this->config('usajobs_integration.parameters')
      ->set('Organization', $form_state->getValue('Organization'))
      ->set('Keyword', $form_state->getValue('Keyword'))
      ->set('KeywordExclusion', $form_state->getValue('KeywordExclusion'))
      ->set('KeywordFilter', $form_state->getValue('KeywordFilter'))
      ->set('PositionTitle', $form_state->getValue('PositionTitle'))
      ->set('RemunerationMinimumAmount', $form_state->getValue('RemunerationMinimumAmount'))
      ->set('RemunerationMaximumAmount', $form_state->getValue('RemunerationMaximumAmount'))
      ->set('JobGradeCode', $form_state->getValue('JobGradeCode'))
      ->set('PayGradeLow', $form_state->getValue('PayGradeLow'))
      ->set('PayGradeHigh', $form_state->getValue('PayGradeHigh'))
      ->set('JobCategoryCode', $form_state->getValue('JobCategoryCode'))
      ->set('LocationName', $form_state->getValue('LocationName'))
      ->set('Radius', $form_state->getValue('Radius'))
      ->set('PositionOfferingTypeCode', $form_state->getValue('PositionOfferingTypeCode'))
      ->set('TravelPercentage', $form_state->getValue('TravelPercentage'))
      ->set('PositionScheduleTypeCode', $form_state->getValue('PositionScheduleTypeCode'))
      ->set('RelocationIndicator', $form_state->getValue('RelocationIndicator'))
      ->set('SecurityClearanceRequired', $form_state->getValue('SecurityClearanceRequired'))
      ->set('SupervisoryStatus', $form_state->getValue('SupervisoryStatus'))
      ->set('DatePosted', $form_state->getValue('DatePosted'))
      ->set('WhoMayApply', $form_state->getValue('WhoMayApply'))
      ->set('SES', $form_state->getValue('SES'))
      ->set('Student', $form_state->getValue('Student'))
      ->set('Internship', $form_state->getValue('Internship'))
      ->set('RecentGrad', $form_state->getValue('RecentGrad'))
      ->set('ResultsPerPage', $form_state->getValue('ResultsPerPage'))
      ->save();
  }

}
