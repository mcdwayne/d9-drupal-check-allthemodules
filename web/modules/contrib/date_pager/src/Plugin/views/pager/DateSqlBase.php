<?php

namespace Drupal\date_pager\Plugin\views\pager;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\date_pager\PagerDate;

/**
 * Date pager plugin implementation.
 *
 * Lets you choose a date field from the view's table
 * and page it with a flexible granularity.
 *
 * @author Kate Heinlein
 */
abstract class DateSqlBase extends PagerPluginBase implements CacheableDependencyInterface {

  public $mindDate = NULL;
  public $maxDate = NULL;
  public $activeDate;
  public $dateformatstring;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Sets date format String.
    $this->dateformatstring = $this->getFormatString($this->options['granularity']);
  }

  /**
   * Helper function for date format.
   *
   * @param int $granularity
   *   Numerical expression of desired granularity.
   *
   * @return string
   *   DateTime date format string part w/ granularity.
   */
  private function getFormatString($granularity) {
    $dateparts = ['Y', '-m', '-d', '\TH', ':i'];
    return implode('', array_slice($dateparts, 0, $granularity + 1));
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['granularity'] = ['default' => '3'];
    $options['default_page'] = ['default' => 'now'];
    $options['date_field'] = ['default' => FALSE];

    return $options;
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Delect the granularity of the pager.
    $form['granularity'] = [
      '#type' => 'select',
      '#title' => $this->t('Granularity'),
      '#description' => $this->t("Select the maximumgranularity of the pager"),
      '#options' => [
        0 => $this->t('Year'),
        1 => $this->t('Month'),
        2 => $this->t('Day'),
        3 => $this->t('Hour'),
        4 => $this->t('Minute'),
      ],
      '#default_value' => $this->options['granularity'],
    ];

    // Time to be displayed if no page was selected.
    $form['default'] = [
      '#type' => 'select',
      '#title' => $this->t('Default time'),
      '#description' => $this->t("Time to be displayed if no page was selected"),
      '#options' => [
        'earliest' => $this->t('earliest'),
        'now' => $this->t('current'),
        'lastest' => $this->t('latest'),
      ],
      '#default_value' => $this->options['default_page'],
    ];

    // Date field to page on.
    $form['date_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Date field'),
      '#description' => $this->t("Select the Date field to page on"),
      '#options' => $this->getDateFieldOptions(),
      '#default_value' => $this->options['date_field'],
    ];
  }

  /**
   * Lists all the datefields from fields and entity keys.
   *
   * @return array
   *   List of available DateTime fields.
   */
  private function getDateFieldOptions() {

    // Available Date field options.
    $date_fields = [];

    $entityManager = \Drupal::service('entity.manager');
    $entityFieldManager = \Drupal::service('entity_field.manager');

    $entity_type_id = $this->view->getBaseEntityType()->id();

    $field_map = $entityFieldManager->getFieldMap();
    $storage = $entityFieldManager->getFieldStorageDefinitions($entity_type_id);
    $mapping = $entityManager->getStorage($entity_type_id)->getTableMapping();

    foreach ($field_map[$entity_type_id] as $machine_name => $field) {

      // Look for datetime fields.
      if ($field['type'] == 'datetime') {

        // Decide wheather its an entity key or field.
        if ($this->view->getBaseEntityType()->hasKey($machine_name)) {
          // It has an entity key -> it's om the enitity's table.
          $identifyer = "${entity_type_id}.${machine_name}";
        }
        else {
          // Otherwise it's a field, and it's in a field table.
          $tableName = $mapping->getDedicatedDataTableName($storage[$machine_name]);
          $identifyer = "${tableName}.${machine_name}_value";
        }

        // Get the labels.
        $date_fields[$identifyer] = "";
        foreach ($field['bundles'] as $bundle) {
          $bundle_fields = $entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
          // This can get very long.
          // @TODO: Find a nicer way to list a lot of bundles
          $date_fields[$identifyer] .= $bundle_fields[$machine_name]->getLabel() . "($bundle)";
        }
      }
    }

    return $date_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {

    // Check granularity values.
    $granularity = $form_state->getValue(['pager_options', 'granularity']);
    if (!is_numeric($granularity) || intval($granularity) < 0 || intval($granularity) > 4) {
      $form_state->setErrorByName('pager_options][granularity', $this->t('Please select a valid granularity.'));
    }

    // Check date_field value.
    $date_field = $form_state->getValue(['pager_options', 'date_field']);
    if (empty($date_field)) {
      // @TODO: Check if valid date field!
      $form_state->setErrorByName('pager_options][date_field', $this->t('Please select a valid date field.'));
    }
  }

  /**
   * Adds a date pager query to the field.
   */
  public function query() {

    list($base_table, $field) = explode('.', $this->options['date_field']);
    $alias = $field;
    $this->view->query->addField($base_table, $field, $alias);

    // Checks if the date field is used, e.g. in a contextual filter.
    $found = FALSE;
    if (isset($this->view->query->where[0]['conditions'])) {
      foreach ($this->view->query->where[0]['conditions'] as &$condition) {
        if ($condition['field'] == $alias) {
          $condition['value'] = $this->activeDate . '%';
          $condition['operator'] = 'LIKE';
          $found = TRUE;
        }
      }
    }

    // If the date field is not already used, add a new where query.
    if (!$found) {
      $this->view->query->addWhere(1, $alias, $this->activeDate . '%', 'LIKE');
    }
  }

  /**
   * Get field date range of min max values.
   *
   * @return array
   *   Min and max values from the query.
   */
  private function getDateRange() {

    list($base_table, $field) = explode('.', $this->options['date_field']);

    $query = \Drupal::database()->select($base_table);
    $query->addExpression('MIN(' . $field . ')', 'min');
    $query->addExpression('MAX(' . $field . ')', 'max');

    return $query->execute()->fetchAssoc();
  }

  /**
   * Sets the current page date.
   *
   * @param string $time
   *   If provided, the active date will be set to the timestamp.
   */
  public function setCurrentPage($time = NULL) {

    // Get min and max values for the selected field.
    $range = $this->getDateRange();
    $this->minDate = new PagerDate($range['min'], $this->dateformatstring);
    $this->maxDate = new PagerDate($range['max'], $this->dateformatstring);

    // Try to get activeDate from URL Parameter.
    if ($date_parameter = $this->view->getRequest()->query->get('date')) {
      $this->activeDate = new PagerDate($date_parameter);
    }
    else {
      switch ($this->options['default_page']) {
        case 'ealiest':
          $time = $this->minDate;
          break;

        case 'latest':
          $time = $this->maxDate;
          break;

        default:
          $time = date($this->dateformatstring);
          $time = new PagerDate($time, $this->dateformatstring);
          break;
      }
      $this->activeDate = $time;
    }
  }

  /**
   * Returns a string to display as the clickable title for the pager plugin.
   *
   * @return string
   *   Title.
   */
  public function summaryTitle() {
    return $this->t('Page by date');
  }

  /**
   * {@inheritdoc}
   */
  public function usePager() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function useCountQuery() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // The rendered link needs to play well with any other query parameter used
    // on the page, like other pagers and exposed filter.
    return ['url.query_args'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
