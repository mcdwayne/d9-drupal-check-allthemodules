<?php

namespace Drupal\ip_anon\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for ip_anon module.
 */
class IpAnonSettings extends ConfigFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs an IpAnonSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $connection, DateFormatterInterface $date_formatter) {
    parent::__construct($config_factory);
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ip_anon_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ip_anon.settings');
    $config->set('policy', $form_state->getValue('policy'));
    foreach (Element::children($form['period']) as $variable) {
      $config->set($variable, $form_state->getValue($variable));
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ip_anon.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ip_anon.settings');
    $form['policy'] = [
      '#type' => 'radios',
      '#title' => $this->t('Retention policy'),
      '#options' => [$this->t('Preserve IP addresses'), $this->t('Anonymize IP addresses')],
      '#description' => $this->t('This setting may be used to temporarily disable IP anonymization.'),
      '#default_value' => $config->get('policy'),
    ];
    $form['period'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Retention period'),
      '#description' => $this->t('IP addresses older than the retention period will be anonymized.'),
    ];
    $intervals = [
      0,
      30,
      60,
      120,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      5400,
      7200,
      10800,
      21600,
      32400,
      43200,
      64800,
      86400,
      172800,
      259200,
      345600,
      604800,
      1209600,
      2419200,
      4838400,
      9676800,
      31536000,
    ];
    $options = array_combine($intervals, array_map([$this->dateFormatter, 'formatInterval'], $intervals));
    module_load_include('inc', 'ip_anon');
    foreach (ip_anon_tables() as $table => $columns) {
      $form['period']["period_$table"] = [
        '#type' => 'select',
        '#title' => $this->t('@table table', ['@table' => $table]),
        '#options' => $options,
        '#default_value' => $config->get("period_$table"),
        '#description' => new FormattableMarkup('@description', ['@description' => $this->getTableDescription($table)]),
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * Returns table description.
   */
  public function getTableDescription($table) {
    if ($table == 'sessions') {
      return drupal_get_module_schema('system', 'sessions')['description'];
    }
    elseif (method_exists($this->connection->schema(), 'getComment')) {
      return $this->connection->schema()->getComment($table);
    }
  }

}
