<?php

/**
 * @file
 * Contains \Drupal\monitoring\SensorListBuilder.
 */

namespace Drupal\monitoring;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Url;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a class to build a listing of sensor config entities.
 *
 * @see \Drupal\monitoring\Entity\SensorConfig
 */
class SensorListBuilder extends ConfigEntityListBuilder implements FormInterface {

  /**
   * {@inheritdoc}
   */
  protected $limit = FALSE;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Overrides the original Header completely.
    $header['category'] = $this->t('Category');
    $header['label'] = $this->t('Label');
    $header['description'] = $this->t('Description');
    $header['operations'] = $this->t('Operations');

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\monitoring\Entity\SensorConfig $entity */
    $row['category']['data'] = $entity->getCategory();
    $row['category']['class'][] = 'table-filter-category';
    $row['label']['data'] = $this->getLabel($entity);
    $row['label']['class'][] = 'table-filter-text-source';

    $plugin_definition = monitoring_sensor_manager()->getSensorConfigByName($entity->id())->getPlugin()->getPluginDefinition();
    $row['description']['data'] = array(
      '#type' => 'details',
      '#title' => $entity->getDescription() ?: $this->t('Details'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#attributes' => array('class' => array('monitoring-sensor-details')),
    );
    $row['description']['data'][$entity->id()]['id'] = [
      '#type' => 'item',
      '#title' => $this->t('Sensor ID'),
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
      '#markup' => '<span class="table-filter-text-source">' . $entity->id() . '</span>',
    ];
    $row['description']['data'][$entity->id()]['sensor_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Sensor type'),
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
      '#markup' => '<span class="table-filter-sensor-type">' . $plugin_definition['label'] . '</span>',
    ];
    $url = Url::fromRoute('entity.monitoring_sensor_config.details_form', array('monitoring_sensor_config' => $entity->id()));

    $row = $row + parent::buildRow($entity);

    // Adds the link to details page if sensor is enabled.
    /** @var \Drupal\monitoring\Entity\SensorConfig $sensor_config */
    $sensor_config = SensorConfig::load($entity->id());
    if ($sensor_config->isEnabled()) {
      $row['operations']['data']['#links']['details'] = array(
        'title' => 'Details',
        'url' => $url,
      );
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sensor_overview_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $form = \Drupal::formBuilder()->getForm($this);
    $form['sensors']['#attributes']['class'][] = 'monitoring-sensor-overview';
    $form['#attached']['library'][] = 'monitoring/monitoring.sensor.overview';
    $form['#attached']['library'][] = 'monitoring/monitoring';
    $form['#attached']['library'][] = 'core/drupal.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $categories = [];
    $options = [];
    $default_value = [];

    /** @var \Drupal\monitoring\Entity\SensorConfig $entity */
    foreach ($this->load() as $entity) {
      $row = $this->buildRow($entity);
      $options[$entity->id()] = $row;
      $default_value[$entity->id()] = $entity->isEnabled();

      // Get all sensor categories.
      if (!isset($categories[$entity->getCategory()])) {
        $categories[$entity->getCategory()] = $entity->getCategory();
      }

      $plugin_definition = monitoring_sensor_manager()->getSensorConfigByName($entity->id())->getPlugin()->getPluginDefinition();
      // Get all sensor plugin types.
      $plugin_label_key = $plugin_definition['label']->render();
      if (!isset($sensor_types[$plugin_label_key])) {
        $sensor_types[$plugin_label_key] = $plugin_definition['label'];
      }
    }
    asort($sensor_types);

    $form['filters'] = array(
      '#type' => 'fieldset',
      '#attributes' => array(
        'class' => array('table-filter', 'js-show', 'form--inline'),
      ),
      '#weight' => -10,
      '#title' => $this->t('Filter'),
    );
    $form['filters']['sensor_type'] = array(
      '#type' => 'select',
      '#empty_option' => $this->t('- All -'),
      '#title' => $this->t('Sensor type'),
      '#options' => $sensor_types,
      '#attributes' => array(
        'class' => array('table-filter-select-sensor-type'),
      ),
    );
    $form['filters']['category'] = array(
      '#type' => 'select',
      '#empty_option' => $this->t('- All -'),
      '#title' => $this->t('Category'),
      '#options' => $categories,
      '#attributes' => array(
        'class' => array('table-filter-select-category'),
      ),
    );
    $form['filters']['text'] = array(
      '#type' => 'search',
      '#title' => $this->t('Sensor label or sensor id'),
      '#size' => 40,
      '#placeholder' => $this->t('Enter a sensor label or sensor id'),
      '#attributes' => array(
        'class' => array('table-filter-text'),
        'data-table' => '.monitoring-sensor-overview',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the sensor label or sensor id to filter by.'),
      ),
    );
    $form['sensors'] = array(
      '#type' => 'tableselect',
      '#header' => $this->buildHeader(),
      '#options' => $options,
      '#default_value' => $default_value,
      '#attributes' => array(
        'id' => 'monitoring-sensors-config-overview',
      ),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update enabled sensors'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('sensors') as $sensor_id => $enabled) {
      /** @var \Drupal\monitoring\Entity\SensorConfig $sensor */
      $sensor = SensorConfig::load($sensor_id);
      if ($enabled) {
        $sensor->status = TRUE;
      }
      else {
        $sensor->status = FALSE;
      }
      $sensor->save();
    }
    drupal_set_message($this->t('Configuration has been saved.'));
  }

}
