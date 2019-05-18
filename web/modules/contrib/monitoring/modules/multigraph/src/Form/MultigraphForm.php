<?php
/**
 * @file
 * Contains \Drupal\monitoring_multigraph\Form\MultigraphForm.
 */

namespace Drupal\monitoring_multigraph\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Multigraph settings form controller.
 */
class MultigraphForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;
    /** @var \Drupal\monitoring_multigraph\MultigraphInterface $multigraph */
    $multigraph = $this->entity;

    // Find sensors that can be added.
    $sensor_ids = \Drupal::entityQuery('monitoring_sensor_config')
      ->condition('status', TRUE)
      ->execute();
    // Remove already added sensors.
    $sensor_ids = array_diff($sensor_ids, array_keys($multigraph->getSensorsRaw()));
    ksort($sensor_ids);
    /** @var \Drupal\monitoring\Entity\SensorConfig[] $sensors */
    $sensors = $this->entityTypeManager
      ->getStorage('monitoring_sensor_config')
      ->loadMultiple($sensor_ids);
    uasort($sensors, "\Drupal\monitoring\Entity\SensorConfig::sort");

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 60,
      '#default_value' => $multigraph->label(),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('ID'),
      '#maxlength' => 32,
      '#default_value' => $multigraph->id(),
      '#description' => $this->t("ID of the multigraph"),
      '#required' => TRUE,
      '#disabled' => !$multigraph->isNew(),
      '#machine_name' => array(
        'exists' => 'Drupal\monitoring_multigraph\Entity\Multigraph::load',
      ),
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $multigraph->getDescription(),
    );

    // Fieldset for sensor list elements.
    $form['sensor_list'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Sensors'),
      '#prefix' => '<div id="selected-sensors">',
      '#suffix' => '</div>',
      '#tree' => FALSE,
    );

    // Create an array suitable for the sensor_add_select element.
    $sensors_options = array();
    foreach ($sensors as $sensor) {
      if ($sensor->isNumeric()) {
        $sensors_options[$sensor->id()] = $sensor->getCategory() . ': ' . $sensor->getLabel();
      }
    }

    // Select element for available sensors.
    $form['sensor_list']['add'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['sensor_list']['add']['sensor_add_select'] = array(
      '#type' => 'select',
      '#title' => $this->t('Available sensors'),
      '#options' => $sensors_options,
      '#empty_value' => '',
    );

    $form['sensor_list']['add']['sensor_add_button'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add sensor'),
      '#ajax' => array(
        'wrapper' => 'selected-sensors',
        'callback' => array($this, 'sensorsReplace'),
        'method' => 'replace',
      ),
      '#submit' => array('::addSensorSubmit'),
    );

    // Table for included sensors.
    $form['sensor_list']['sensors'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => array(
        'category' => $this->t('Category'),
        'label' => $this->t('Sensor label'),
        'message' => $this->t('Sensor message'),
        'weight' => $this->t('Weight'),
        'operations' => $this->t('Operations'),
      ),
      '#empty' => $this->t(
        'Select and add sensors above to include them in this multigraph.'
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'sensors-table-weight',
        ),
      ),
    );

    // Fill the sensors table with form elements for each sensor.
    $weight = 0;
    foreach ($multigraph->getSensors() as $sensor) {
      $form['sensor_list']['sensors'][$sensor->id()] = array(
        'category' => array(
          '#markup' => $sensor->getCategory(),
        ),
        'label' => array(
          '#type' => 'textfield',
          '#default_value' => $sensor->label(),
          '#title' => $this->t('Custom sensor label'),
          '#title_display' => 'invisible',
          '#required' => TRUE,
          '#description' => $sensor->getDescription(),
        ),
        'message' => array(
          '#markup' => monitoring_sensor_run($sensor->id())->getMessage(),
        ),
        'weight' => array(
          '#type' => 'weight',
          '#title' => $this->t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => $weight++,
          '#attributes' => array(
            'class' => array('sensors-table-weight'),
          ),
        ),
        'operations' => array(
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#description' => $this->t('Exclude sensor from multigraph'),
          '#name' => 'remove_' . $sensor->id(),
          '#ajax' => array(
            'wrapper' => 'selected-sensors',
            'callback' => array($this, 'sensorsReplace'),
            'method' => 'replace',
          ),
          '#submit' => array('::removeSensorSubmit'),
        ),
        '#attributes' => array(
          'class' => array('draggable'),
        ),
      );
    }

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * Returns the updated 'sensors_add' fieldset for replacement by ajax.
   *
   * @param array $form
   *   The updated form structure array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state structure.
   *
   * @return array
   *   The updated form component for the selected sensors.
   */
  public function sensorsReplace(array $form, FormStateInterface $form_state) {
    return $form['sensor_list'];
  }

  /**
   * Adds sensor to entity when 'Add sensor' button is pressed.
   *
   * @param array $form
   *   The form structure array
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state structure.
   */
  public function addSensorSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    /** @var \Drupal\monitoring_multigraph\Entity\Multigraph $multigraph */
    $multigraph = $this->entity;

    // Add any selected sensor to entity.
    if ($sensor_name = $form_state->getValue(array('sensor_add_select'))) {
      $sensor_label = $this->entityTypeManager->getStorage('monitoring_sensor_config')->load($sensor_name)->getLabel();
      $multigraph->addSensor($sensor_name);
      drupal_set_message($this->t('Sensor "@sensor_label" added. You have unsaved changes.', array('@sensor_label' => $sensor_label)), 'warning');
    }
    else {
      drupal_set_message($this->t('No sensor selected.'), 'warning');
    }
  }

  /**
   * Removes sensor from entity when 'Remove' button is pressed for sensor.
   *
   * @param array $form
   *   The form structure array
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state structure.
   */
  public function removeSensorSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    /** @var \Drupal\monitoring_multigraph\MultigraphInterface $multigraph */
    $multigraph = $this->entity;

    // Remove sensor as indicated by triggering_element.
    $button_name = $form_state->getTriggeringElement()['#name'];
    $sensor_name = substr($button_name, strlen('remove_'));
    $sensor_label = $this->entityTypeManager->getStorage('monitoring_sensor_config')->load($sensor_name)->getLabel();
    $multigraph->removeSensor($sensor_name);
    drupal_set_message($this->t('Sensor "@sensor_label" removed.  You have unsaved changes.', array('@sensor_label' => $sensor_label)), 'warning');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirect('entity.monitoring_multigraph.list');
    drupal_set_message($this->t('Multigraph settings saved.'));
  }

  /**
   * @inheritDoc
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // Unset an empty sensors key or the sensors array is overwritten with an
    // empty string.
    if (!$form_state->getValue('sensors')) {
      $form_state->unsetValue('sensors');
    }
    parent::copyFormValuesToEntity($entity, $form, $form_state);
  }

}
