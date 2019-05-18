<?php
/**
 * @file
 *   Contains \Drupal\monitoring\Form\SensorForm.
 */

namespace Drupal\monitoring\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\monitoring\SensorConfigInterface;

/**
 * Sensor settings form controller.
 */
class SensorForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\monitoring\SensorConfigInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $sensor_config = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $sensor_config->getLabel(),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('ID'),
      '#maxlength' => 255,
      '#default_value' => $sensor_config->id(),
      '#description' => $this->t("ID of the sensor"),
      '#required' => TRUE,
      '#disabled' => !$sensor_config->isNew(),
      '#machine_name' => array(
        'exists' => 'Drupal\monitoring\Entity\SensorConfig::load',
      ),
    );

    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Check to have the sensor trigger.'),
      '#default_value' => $sensor_config->status(),
    );

    if ($sensor_config->isNew()) {
      $plugin_types = array();
      foreach (monitoring_sensor_manager()->getDefinitions() as $plugin_id => $definition) {
        if ($definition['addable'] == TRUE) {
          $plugin_types[$plugin_id] = (string) $definition['label'];
        }
      }
      uasort($plugin_types, 'strnatcasecmp');
      $form['plugin_id'] = array(
        '#type' => 'select',
        '#options' => $plugin_types,
        '#title' => $this->t('Sensor Plugin'),
        '#limit_validation_errors' => array(array('plugin_id')),
        '#submit' => array('::submitSelectPlugin'),
        '#required' => TRUE,
        '#executes_submit_callback' => TRUE,
        '#ajax' => array(
          'callback' => '::ajaxReplacePluginSpecificForm',
          'wrapper' => 'monitoring-sensor-plugin',
          'method' => 'replace',
        ),
      );

      $form['select_plugin'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Select sensor'),
        '#limit_validation_errors' => array(array('plugin_id')),
        '#submit' => array('::submitSelectPlugin'),
        '#attributes' => array('class' => array('js-hide')),
      );

    }
    else {
      // @todo odd name but this can not be set to plugin_id.
      $form['old_plugin_id'] = array(
        '#type' => 'item',
        '#title' => $this->t('Sensor Plugin'),
        '#markup' => (string) monitoring_sensor_manager()->getDefinition($sensor_config->plugin_id)['label'],
      );
    }

    $form['plugin_container'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="monitoring-sensor-plugin">',
      '#suffix' => '</div>',
    );

    if (isset($sensor_config->plugin_id) && $plugin = $sensor_config->getPlugin()) {
      $form['plugin_container']['settings'] = array(
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t('Sensor plugin settings'),
        '#tree' => TRUE,
      );
      $form['plugin_container']['settings'] += (array) $plugin->buildConfigurationForm($form['plugin_container']['settings'], $form_state);

      $settings = $sensor_config->getSettings();
      foreach ($settings as $key => $value) {
        if (!isset($form['plugin_container']['settings'][$key])) {
          $form['plugin_container']['settings'][$key] = array(
            '#type' => 'value',
            '#value' => $value
          );
        }
      }
      $form['plugin_container']['category'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Category'),
        '#maxlength' => 255,
        '#autocomplete_route_name' => 'monitoring.category_autocomplete',
        '#default_value' => $sensor_config->getCategory(),
      );

      $form['plugin_container']['description'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Description'),
        '#maxlength' => 255,
        '#default_value' => $sensor_config->getDescription(),
      );


      $form['plugin_container']['caching_time'] = array(
        '#type' => 'number',
        '#title' => $this->t('Cache Time'),
        '#maxlength' => 10,
        '#default_value' => $sensor_config->getCachingTime(),
        '#description' => $this->t("The caching time for the sensor. Empty to disable caching."),
        '#field_suffix' => $this->t('seconds'),
      );

      $value_types = [];
      foreach (monitoring_value_types() as $value_type => $info) {
        $value_types[$value_type] = $info['label'];
      }

      $value_type = $sensor_config->getValueType();
      $form['plugin_container']['value_type'] = array(
        '#type' => 'select',
        '#title' => $this->t('Expected value type'),
        '#options' => $value_types,
        '#default_value' => $value_type,
        '#limit_validation_errors' => array(array('value_type')),
        '#submit' => array('::submitSelectPlugin'),
        '#required' => TRUE,
        '#executes_submit_callback' => TRUE,
        '#ajax' => array(
          'callback' => '::ajaxReplacePluginSpecificForm',
          'wrapper' => 'monitoring-sensor-plugin',
          'method' => 'replace',
        ),
        '#access' => $sensor_config->getPlugin()->getConfigurableValueType(),
      );

      $form['plugin_container']['value_label'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Value Label'),
        '#maxlength' => 255,
        '#default_value' => $sensor_config->getValueLabel(),
        '#description' => $this->t("The value label represents the units of the sensor value."),
        '#access' => $value_type != 'no_value',
      );

      if ($this->entity->isNumeric()) {
        $form['plugin_container']['thresholds'] = array(
          '#type' => 'details',
          '#title' => $this->t('Sensor thresholds'),
          '#description' => $this->t('Here you can set limit values that switch the sensor to a given status.'),
          '#prefix' => '<div id="monitoring-sensor-thresholds">',
          '#suffix' => '</div>',
          '#open' => TRUE,
          '#tree' => TRUE,
        );
        $this->thresholdsForm($form, $form_state);
      }

    }

    return $form;
  }

  /**
   * Handles switching the configuration type selector.
   */
  public function ajaxReplacePluginSpecificForm($form, FormStateInterface $form_state) {
    return $form['plugin_container'];
  }

  /**
   * Handles submit call when sensor type is selected.
   */
  public function submitSelectPlugin(array $form, FormStateInterface $form_state) {
    $this->entity = $this->buildEntity($form, $form_state);

    // Set default configuration of the sensor.
    $default_config = (array) $this->entity->getPlugin()->getDefaultConfiguration();
    $default_config += array('settings' => array());
    foreach ($default_config as $key => $value) {
      $this->entity->set($key, $value);
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for threshold sensors settings form.
   */
  function ajaxReplaceThresholdsForm($form, FormStateInterface $form_state) {
    return $form['plugin_container']['thresholds'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\monitoring\SensorConfigInterface $sensor_config */
    $sensor_config = $this->entity;
    /** @var \Drupal\monitoring\SensorPlugin\SensorPluginInterface $plugin */
    if ($sensor_config->isNew()) {
      $plugin_id = $form_state->getValue('plugin_id');
      $plugin = monitoring_sensor_manager()->createInstance($plugin_id, array('sensor_config' => $this->entity));
    }
    else {
      $plugin = $sensor_config->getPlugin();
    }

    $plugin->validateConfigurationForm($form, $form_state);

    if ($this->entity->isNumeric()) {
      $this->validateThresholdsForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\monitoring\SensorConfigInterface $sensor_config */
    $sensor_config = $this->entity;
    $plugin = $sensor_config->getPlugin();

    $plugin->submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $sensor_config = $this->entity;
    $sensor_id = $form_state->getValue('id');

    if ($sensor_config->isEnabled()) {
      $route = 'entity.monitoring_sensor_config.details_form';
    }
    else {
      $route = 'entity.monitoring_sensor_config.edit_form';
    }
    $form_state->setRedirect('monitoring.sensors_overview_settings');
    $url = Url::fromRoute($route, array('monitoring_sensor_config' => $sensor_id));

    // Message with the link to sensor details page.
    drupal_set_message(t('Sensor <a href="@url">@label</a> saved.', array(
      '@url' => $url->toString(),
      '@label' => $form_state->getValue('label')
    )));
  }

  /**
   * Settings form page title callback.
   *
   * @param \Drupal\monitoring\SensorConfigInterface $monitoring_sensor_config
   *   The Sensor config.
   *
   * @return string
   */
  public function formTitle(SensorConfigInterface $monitoring_sensor_config) {
    return $this->t('@label settings (@category)', array('@category' => $monitoring_sensor_config->getCategory(), '@label' => $monitoring_sensor_config->getLabel()));
  }

  /**
   * Builds the threshold settings form.
   */
  protected function thresholdsForm(array &$form, FormStateInterface $form_state) {

    $type = $form_state->getValue(array('thresholds', 'type'));

    if (empty($type)) {
      $type = $this->entity->getThresholdsType();
    }

    $form['plugin_container']['thresholds']['type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Threshold type'),
      '#options' => array(
        'none' => $this->t('- None -'),
        'exceeds' => $this->t('Exceeds'),
        'falls' => $this->t('Falls'),
        'inner_interval' => $this->t('Inner interval'),
        'outer_interval' => $this->t('Outer interval'),
      ),
      '#default_value' => $type,
      '#ajax' => array(
        'callback' => '::ajaxReplaceThresholdsForm',
        'wrapper' => 'monitoring-sensor-thresholds',
      ),
    );

    switch ($type) {
      case 'exceeds':
        $form['plugin_container']['thresholds']['type']['#description'] = $this->t('The sensor will be set to the corresponding status if the value exceeds the limits.');
        $form['plugin_container']['thresholds']['warning'] = array(
          '#type' => 'number',
          '#title' => $this->t('Warning'),
          '#default_value' => $this->entity->getThresholdValue('warning'),
        );
        $form['plugin_container']['thresholds']['critical'] = array(
          '#type' => 'number',
          '#title' => $this->t('Critical'),
          '#default_value' => $this->entity->getThresholdValue('critical'),
        );
        break;

      case 'falls':
        $form['plugin_container']['thresholds']['type']['#description'] = $this->t('The sensor will be set to the corresponding status if the value falls below the limits.');
        $form['plugin_container']['thresholds']['warning'] = array(
          '#type' => 'number',
          '#title' => $this->t('Warning'),
          '#default_value' => $this->entity->getThresholdValue('warning'),
        );
        $form['plugin_container']['thresholds']['critical'] = array(
          '#type' => 'number',
          '#title' => $this->t('Critical'),
          '#default_value' => $this->entity->getThresholdValue('critical'),
        );
        break;

      case 'inner_interval':
        $form['plugin_container']['thresholds']['type']['#description'] = $this->t('The sensor will be set to the corresponding status if the value is within the limits.');
        $form['plugin_container']['thresholds']['warning_low'] = array(
          '#type' => 'number',
          '#title' => $this->t('Warning low'),
          '#default_value' => $this->entity->getThresholdValue('warning_low'),
        );
        $form['plugin_container']['thresholds']['warning_high'] = array(
          '#type' => 'number',
          '#title' => $this->t('Warning high'),
          '#default_value' => $this->entity->getThresholdValue('warning_high'),
        );
        $form['plugin_container']['thresholds']['critical_low'] = array(
          '#type' => 'number',
          '#title' => $this->t('Critical low'),
          '#default_value' => $this->entity->getThresholdValue('critical_low'),
        );
        $form['plugin_container']['thresholds']['critical_high'] = array(
          '#type' => 'number',
          '#title' => $this->t('Critical high'),
          '#default_value' => $this->entity->getThresholdValue('critical_high'),
        );
        break;

      case 'outer_interval':
        $form['plugin_container']['thresholds']['type']['#description'] = $this->t('The sensor will be set to the corresponding status if the value is outside of the limits.');
        $form['plugin_container']['thresholds']['warning_low'] = array(
          '#type' => 'number',
          '#title' => $this->t('Warning low'),
          '#default_value' => $this->entity->getThresholdValue('warning_low'),
        );
        $form['plugin_container']['thresholds']['warning_high'] = array(
          '#type' => 'number',
          '#title' => $this->t('Warning high'),
          '#default_value' => $this->entity->getThresholdValue('warning_high'),
        );
        $form['plugin_container']['thresholds']['critical_low'] = array(
          '#type' => 'number',
          '#title' => $this->t('Critical low'),
          '#default_value' => $this->entity->getThresholdValue('critical_low'),
        );
        $form['plugin_container']['thresholds']['critical_high'] = array(
          '#type' => 'number',
          '#title' => $this->t('Critical high'),
          '#default_value' => $this->entity->getThresholdValue('critical_high'),
        );
        break;
    }

    return $form;
  }

  /**
   * Sets a form error for the given threshold key.
   *
   * @param string $threshold_key
   *   Key of the threshold value form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal form state object.
   * @param string $message
   *   The validation message.
   */
  protected function setThresholdFormError($threshold_key, FormStateInterface $form_state, $message) {
    $form_state->setErrorByName('plugin_container[thresholds][' . $threshold_key, $message);
  }

  /**
   * {@inheritdoc}
   */
  public function validateThresholdsForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(array('thresholds'));
    $type = $values['type'];

    switch ($type) {
      case 'exceeds':
        if (!empty($values['warning']) && !empty($values['critical']) && $values['warning'] >= $values['critical']) {
          $this->setThresholdFormError('warning', $form_state, $this->t('Warning must be lower than critical or empty.'));
        }
        break;

      case 'falls':
        if (!empty($values['warning']) && !empty($values['critical']) && $values['warning'] <= $values['critical']) {
          $this->setThresholdFormError('warning', $form_state, $this->t('Warning must be higher than critical or empty.'));
        }
        break;

      case 'inner_interval':
        if (empty($values['warning_low']) && !empty($values['warning_high']) || !empty($values['warning_low']) && empty($values['warning_high'])) {
          $this->setThresholdFormError('warning_low', $form_state, $this->t('Either both warning values must be provided or none.'));
        }
        elseif (empty($values['critical_low']) && !empty($values['critical_high']) || !empty($values['critical_low']) && empty($values['critical_high'])) {
          $this->setThresholdFormError('critical_low', $form_state, $this->t('Either both critical values must be provided or none.'));
        }
        elseif (!empty($values['warning_low']) && !empty($values['warning_high']) && $values['warning_low'] >= $values['warning_high']) {
          $this->setThresholdFormError('warning_low', $form_state, $this->t('Warning low must be lower than warning high or empty.'));
        }
        elseif (!empty($values['critical_low']) && !empty($values['critical_high']) && $values['critical_low'] >= $values['critical_high']) {
          $this->setThresholdFormError('warning_low', $form_state, $this->t('Critical low must be lower than critical high or empty.'));
        }
        elseif (!empty($values['warning_low']) && !empty($values['critical_low']) && $values['warning_low'] >= $values['critical_low']) {
          $this->setThresholdFormError('warning_low', $form_state, $this->t('Warning low must be lower than critical low or empty.'));
        }
        elseif (!empty($values['warning_high']) && !empty($values['critical_high']) && $values['warning_high'] <= $values['critical_high']) {
          $this->setThresholdFormError('warning_high', $form_state, $this->t('Warning high must be higher than critical high or empty.'));
        }
        break;

      case 'outer_interval':
        if (empty($values['warning_low']) && !empty($values['warning_high']) || !empty($values['warning_low']) && empty($values['warning_high'])) {
          $this->setThresholdFormError('warning_low', $form_state, $this->t('Either both warning values must be provided or none.'));
        }
        elseif (empty($values['critical_low']) && !empty($values['critical_high']) || !empty($values['critical_low']) && empty($values['critical_high'])) {
          $this->setThresholdFormError('critical_low', $form_state, $this->t('Either both critical values must be provided or none.'));
        }
        elseif (!empty($values['warning_low']) && !empty($values['warning_high']) && $values['warning_low'] >= $values['warning_high']) {
          $this->setThresholdFormError('warning_low', $form_state, $this->t('Warning low must be lower than warning high or empty.'));
        }
        elseif (!empty($values['critical_low']) && !empty($values['critical_high']) && $values['critical_low'] >= $values['critical_high']) {
          $this->setThresholdFormError('warning_low', $form_state, $this->t('Critical low must be lower than critical high or empty.'));
        }
        elseif (!empty($values['warning_low']) && !empty($values['critical_low']) && $values['warning_low'] <= $values['critical_low']) {
          $this->setThresholdFormError('warning_low', $form_state, $this->t('Warning low must be higher than critical low or empty.'));
        }
        elseif (!empty($values['warning_high']) && !empty($values['critical_high']) && $values['warning_high'] >= $values['critical_high']) {
          $this->setThresholdFormError('warning_high', $form_state, $this->t('Warning high must be lower than critical high or empty.'));
        }
        break;
    }
  }
}
