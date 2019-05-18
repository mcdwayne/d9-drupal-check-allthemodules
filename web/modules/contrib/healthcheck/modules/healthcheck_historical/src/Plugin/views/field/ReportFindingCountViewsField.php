<?php

namespace Drupal\healthcheck_historical\Plugin\views\field;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\views\Plugin\views\field\NumericField;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field handler for status fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("healthcheck_report_finding_count")
 */
class ReportFindingCountViewsField extends NumericField {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['limit_to_status'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {


    $form['limit_to_status'] = [
      '#title' => $this->t('Status'),
      '#description' => $this->t("Count only findings with the selected status."),
      '#type' => 'select',
      '#options' => $this->getOptions(),
      '#default_value' => $this->options['limit_to_status'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();

    $status = 'all';
    if (!empty($this->options['limit_to_status'])) {
      $statues = FindingStatus::getTextConstants();
      $status = $statues[$this->options['limit_to_status']];
    }

    $this->field_alias = $this->table . '_' . $this->field . '_' . $status;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    foreach ($values as $index => $value) {
      if (empty($value->id)) {
        continue;
      }

      $report_id = $value->id;

      $query = $this->database->select('healthcheck_finding', 'hf');

      $query->addExpression('COUNT(*)', 'count');

      if (!empty($this->options['limit_to_status'])) {
        $status = $this->options['limit_to_status'];
        $query->condition('hf.status', $status);
      }

      $query->condition('hf.report_id', $report_id);

      $result = $query->execute()->fetchField();

      $values[$index]->{$this->field_alias} = $result;
    }
  }

  protected function getOptions() {
    $options = FindingStatus::getLabels();

    $options[0] = $this->t('Count all');

    ksort($options);

    return $options;
  }
}
