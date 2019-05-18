<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\ViewDisplayAggregatorSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Drupal\views\Views;

/**
 * Execute a view display and count the results.
 *
 * @SensorPlugin(
 *   id = "view_display_aggregator",
 *   label = @Translation("View Display Aggregator"),
 *   description = @Translation("Execute a view display and count the results."),
 *   provider = "views",
 *   addable = TRUE
 * )
 */
class ViewDisplayAggregatorSensorPlugin extends SensorPluginBase implements ExtendedInfoSensorPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $configurableValueType = FALSE;

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    $output = [];

    $view_executable = Views::getView($this->sensorConfig->getSetting('view'));
    $display_id = $this->sensorConfig->getSetting('display');
    $view_executable->setDisplay($display_id);
    $view_executable->initDisplay();
    $view_executable->setAjaxEnabled(TRUE);

    // Force ajax mode for the view.
    $display = $view_executable->displayHandlers->get($display_id);
    $display->setOption('use_ajax', TRUE);

    // Get the preview of the view for current display.
    $preview = $view_executable->preview($display_id);

    // Get the query and arguments of the view.
    $query = $view_executable->getQuery()->query();
    $arguments = $query->arguments();

    $output['query'] = array(
      '#type' => 'item',
      '#title' => t('Query'),
      '#markup' => '<pre>' . $query . '</pre>',
    );
    $output['arguments'] = array(
      '#type' => 'item',
      '#title' => t('Arguments'),
      '#markup' => '<pre>' . var_export($arguments, TRUE) . '</pre>',
    );
    $output['view'] = array(
      '#type' => 'fieldset',
      '#title' => t('View preview'),
    );
    $output['view']['preview'] = $preview;

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $view_executable = Views::getView($this->sensorConfig->getSetting('view'));
    // Execute the view query and get the total rows.
    $view_executable->preview($this->sensorConfig->getSetting('display'));
    $records_count = $view_executable->total_rows;
    $result->setValue($records_count);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // View selection.
    $form['view'] = array(
      '#type' => 'select',
      '#options' => $this->getViewsOptions(),
      '#title' => t('View'),
      '#default_value' => $this->sensorConfig->getSetting('view'),
      '#required' => TRUE,
      '#limit_validation_errors' => array(array('settings', 'view')),
      '#submit' => array(array($this, 'submitSelectView')),
      '#executes_submit_callback' => TRUE,
      '#ajax' => array(
        'callback' => '::ajaxReplacePluginSpecificForm',
        'wrapper' => 'monitoring-sensor-plugin',
        'method' => 'replace',
      ),
    );
    $form['view_update'] = array(
      '#type' => 'submit',
      '#value' => t('Select view'),
      '#limit_validation_errors' => array(array('settings', 'view')),
      '#submit' => array(array($this, 'submitSelectView')),
      '#attributes' => array('class' => array('js-hide')),
    );

    // Show display selection if a view is selected.
    if ($view = $this->sensorConfig->getSetting('view')) {
      $form['display'] = array(
        '#type' => 'select',
        '#title' => t('Display'),
        '#required' => TRUE,
        '#options' => $this->getDisplayOptions($view),
        '#default_value' => $this->sensorConfig->getSetting('display'),
      );
    }

    return $form;
  }

  /**
   * Gets the available views.
   *
   * @return array
   *   Available views list.
   */
  protected function getViewsOptions() {
    $options = [];
    $views = Views::getAllViews();
    foreach ($views as $view) {
      $options[$view->id()] = $view->label();
    }
    return $options;
  }

  /**
   * Gets the display list for selected view.
   *
   * @param string $view_id
   *   Selected view.
   *
   * @return array
   *   Available displays list.
   */
  protected function getDisplayOptions($view_id) {
    $options = [];

    $displays = Views::getView($view_id)->storage->get('display');
    foreach ($displays as $display) {
      $options[$display['id']] = $display['display_title'];
    }
    return $options;
  }

  /**
   * Handles submit call when view type is selected.
   */
  public function submitSelectView(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    $default_config = array(
      'value_type' => 'number',
    );
    return $default_config;
  }

}
