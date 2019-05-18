<?php

namespace Drupal\easy_google_analytics_counter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class AdminForm.
 */
class AdminForm extends ConfigFormBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AdminForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($config_factory);
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'), $container->get('database'), $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'easy_google_analytics_counter.admin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'easy_google_analytics_counter_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('easy_google_analytics_counter.admin');
    $form['service_account_credentials_json'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://',
      '#multiple' => FALSE,
      '#upload_validators' => [
        'file_validate_extensions' => ['json'],
        'file_validate_size' => [256000],
      ],
      '#title' => $this->t('Service Account Credentials Json File'),
      '#description' => $this->t('Upload the <a href="https://console.developers.google.com/iam-admin/serviceaccounts" target="_blank">Service Account Credentials</a> Json File.'),
      '#default_value' => $config->get('service_account_credentials_json'),
    ];
    $form['application_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Name'),
      '#description' => $this->t('Servive application name generated on <a href="https://console.developers.google.com/iam-admin/serviceaccounts" target="_blank">Service Account Page</a>.'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('application_name'),
    ];
    $form['view_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View ID'),
      '#description' => $this->t('The analytics view easy find it from <a href="https://ga-dev-tools.appspot.com/account-explorer" target="_blank">Account Explorer Page</a>.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('view_id'),
    ];
    $form['sort_dimension'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sort dimension'),
      '#description' => $this->t('Default sort is ga:pageviews. Enter other dimension if need to sort result an other column. The documentation find here <a href="https://developers.google.com/analytics/devguides/reporting/core/dimsmets" target="_blank">Dimensions & Metrics Explorer</a>.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('sort_dimension') ? $config->get('sort_dimension') : 'ga:pageviews',
    ];
    $form['sort_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort mode'),
      '#description' => $this->t('Select a sort mode.'),
      '#options' => ['ASCENDING' => $this->t('Ascending'), 'DESCENDING' => $this->t('Descending')],
      '#size' => 0,
      '#default_value' => $config->get('sort_mode') ? $config->get('sort_mode') : 'DESCENDING',
    ];
    $form['start_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Date range'),
      '#description' => $this->t('Select a value how long time data get from analytics.'),
      '#options' => $this->dateOptionList(),
      '#size' => 0,
      '#default_value' => $config->get('start_date'),
    ];
    $form['number_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items to fetch from Google Analytics in one request'),
      '#description' => $this->t('How many items will be fetched from Google Analytics in one request. Enter number between 10 and 100.'),
      '#default_value' => $config->get('number_items'),
      '#min' => 10,
      '#max' => 100000,
    ];
    $form['independent_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Independent cron'),
      '#description' => $this->t('Check if running independent crontab not the system cron. Call _easy_google_analytics_counter_independent_cron() function from crontab.'),
      '#default_value' => $config->get('independent_cron'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $value = $form_state->getValue('service_account_credentials_json');
    if (!empty($value[0]) && $file = $this->entityTypeManager->getStorage('file')->load($value[0])) {
      $file->setPermanent();
      $file->save();
    }

    $this->config('easy_google_analytics_counter.admin')
      ->set('service_account_credentials_json', $value)
      ->set('application_name', $form_state->getValue('application_name'))
      ->set('view_id', $form_state->getValue('view_id'))
      ->set('sort_dimension', $form_state->getValue('sort_dimension'))
      ->set('sort_mode', $form_state->getValue('sort_mode'))
      ->set('start_date', $form_state->getValue('start_date'))
      ->set('number_items', $form_state->getValue('number_items'))
      ->set('independent_cron', $form_state->getValue('independent_cron'))
      ->save();
  }

  /**
   * Prepare date option list.
   *
   * @return array
   *   The option list.
   */
  private function dateOptionList() {
    return [
      '1' => $this->t('1 day'),
      '2' => $this->t('2 days'),
      '3' => $this->t('3 days'),
      '4' => $this->t('4 days'),
      '5' => $this->t('5 days'),
      '6' => $this->t('6 days'),
      '7' => $this->t('1 week'),
      '8' => $this->t('8 days'),
      '9' => $this->t('9 days'),
      '10' => $this->t('10 days'),
      '11' => $this->t('11 days'),
      '12' => $this->t('12 days'),
      '13' => $this->t('13 days'),
      '14' => $this->t('2 weeks'),
      '21' => $this->t('3 weeks'),
      '28' => $this->t('4 weeks'),
      '30' => $this->t('1 month'),
      '60' => $this->t('2 months'),
      '90' => $this->t('3 months'),
      '120' => $this->t('4 months'),
      '150' => $this->t('5 months'),
      '180' => $this->t('6 months'),
      '210' => $this->t('7 months'),
      '240' => $this->t('8 months'),
      '365' => $this->t('1 year'),
      '730' => $this->t('2 years'),
    ];
  }

}
