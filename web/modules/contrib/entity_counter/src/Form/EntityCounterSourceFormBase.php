<?php

namespace Drupal\entity_counter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\entity_counter\Entity\EntityCounterInterface;
use Drupal\entity_counter\EntityCounterSourceCardinality;
use Drupal\entity_counter\Plugin\EntityCounterSourceInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base form for entity counter sources.
 */
abstract class EntityCounterSourceFormBase extends FormBase {

  /**
   * The entity counter containing the entity counter source to be deleted.
   *
   * @var \Drupal\entity_counter\Entity\EntityCounterInterface
   */
  protected $entityCounter;

  /**
   * The entity counter source to be deleted.
   *
   * @var \Drupal\entity_counter\Plugin\EntityCounterSourceInterface
   */
  protected $entityCounterSource;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_counter_source_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   The entity counter entity.
   * @param string $entity_counter_source
   *   The entity counter source ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws not found exception if the number of source instances for this
   *   entity counter exceeds the source's cardinality.
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityCounterInterface $entity_counter = NULL, $entity_counter_source = NULL) {
    $this->entityCounter = $entity_counter;

    try {
      $this->entityCounterSource = $this->prepareEntityCounterSource($entity_counter_source);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException(sprintf('Invalid entity counter source ID: "%s".', $entity_counter_source));
    }

    // Limit the number of plugin instanced allowed.
    if (!$this->getEntityCounterSource()->getSourceId()) {
      $plugin_id = $this->getEntityCounterSource()->getPluginId();
      $cardinality = $this->getEntityCounterSource()->cardinality();
      $number_of_instances = $entity_counter->getSources($plugin_id)->count();
      if ($cardinality !== EntityCounterSourceCardinality::UNLIMITED && $cardinality <= $number_of_instances) {
        throw new NotFoundHttpException(
          $this->formatPlural(
            $cardinality,
            'Only @number instance is permitted',
            'Only @number instances are permitted',
            ['@number' => $cardinality]
          )
        );
      }
    }

    // Add meta data to entity counter source form.
    $form['#entity_counter_id'] = $this->getEntityCounter()->id();
    $form['#entity_counter_source_id'] = $this->getEntityCounterSource()->getSourceId();
    $form['#entity_counter_source_plugin_id'] = $this->getEntityCounterSource()->getPluginId();

    $request = $this->getRequest();

    $form['description'] = [
      '#type' => 'container',
      'text' => [
        '#markup' => $this->getEntityCounterSource()->description(),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ],
      '#weight' => -20,
    ];
    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->getEntityCounterSource()->getPluginId(),
    ];

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -20,
    ];

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
      '#weight' => -10,
    ];
    $form['general']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $this->getEntityCounterSource()->label(),
      '#required' => TRUE,
      '#attributes' => ['autofocus' => 'autofocus'],
    ];
    $form['general']['source_id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this source instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => $this->getEntityCounterSource()->getSourceId() ?: $this->getUniqueMachineName($this->entityCounterSource),
      '#required' => TRUE,
      '#disabled' => $this->getEntityCounterSource()->getSourceId() ? TRUE : FALSE,
      '#machine_name' => [
        'source' => ['general', 'label'],
        'exists' => [$this, 'exists'],
      ],
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
      '#weight' => 100,
    ];
    $form['advanced']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the %name source.', ['%name' => $this->getEntityCounterSource()->label()]),
      '#return_value' => TRUE,
      '#default_value' => $this->getEntityCounterSource()->isEnabled(),
    ];

    $form['#parents'] = [];
    $form['settings'] = [
      '#tree' => TRUE,
      '#parents' => ['settings'],
    ];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->getEntityCounterSource()->buildConfigurationForm($form['settings'], $subform_state);
    if (isset($form['settings']['#attributes']['novalidate'])) {
      $form['#attributes']['novalidate'] = 'novalidate';
    }

    // Check the URL for a weight, then the entity counter source, otherwise use
    // default.
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->getEntityCounterSource()->getWeight(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    // Disable submit operation if the counter has transactions of this type.
    if ($this->getEntityCounterSource()->getSourceId() &&
      $this->getEntityCounter()->hasTransactions($this->getEntityCounterSource()->getSourceId())) {
      // @TODO: Do not display this message if the form is rebuilding.
      drupal_set_message($this->t('There are transactions for this entity counter source, please delete them before edit this settings.'), 'warning');
      $form['actions']['submit']['#attributes']['disabled'] = 'disabled';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The entity counter source configuration is stored in the 'settings' key
    // in the entity counter, pass that through for validation.
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->getEntityCounterSource()->validateConfigurationForm($form, $subform_state);

    // Process source form state errors.
    $this->processSourceFormErrors($subform_state, $form_state);

    // Update the original form values.
    $form_state->setValue('settings', $subform_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The entity counter source configuration is stored in the 'settings' key
    // in the entity counter, pass that through for validation.
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->getEntityCounterSource()->submitConfigurationForm($form, $subform_state);

    // Update the original form values.
    $form_state->setValue('settings', $subform_state->getValues());

    $configuration = [
      'source_id' => $form_state->getValue('source_id'),
      'label' => $form_state->getValue('label'),
      'status' => $form_state->getValue('status'),
      'weight' => $form_state->getValue('weight'),
      'settings' => $form_state->getValue('settings'),
    ];
    $this->getEntityCounterSource()->setConfiguration($configuration);

    if ($this instanceof EntityCounterSourceAddForm) {
      $this->getEntityCounter()->addSource($this->entityCounterSource);
      drupal_set_message($this->t('The entity counter source was successfully added.'));
    }
    else {
      $this->getEntityCounter()->updateSource($this->entityCounterSource);
      drupal_set_message($this->t('The entity counter source was successfully updated.'));
    }

    $form_state->setRedirectUrl($this->getEntityCounter()->toUrl('canonical'));
  }

  /**
   * Generates a unique machine name for an entity counter source instance.
   *
   * @param \Drupal\entity_counter\Plugin\EntityCounterSourceInterface $source
   *   The entity counter source.
   *
   * @return string
   *   Returns the unique name.
   */
  public function getUniqueMachineName(EntityCounterSourceInterface $source) {
    $suggestion = $source->getPluginId();
    $count = 1;
    $machine_default = $suggestion;
    $instance_ids = $this->getEntityCounter()->getSources()->getInstanceIds();
    while (isset($instance_ids[$machine_default])) {
      $machine_default = $suggestion . '_' . $count++;
    }

    return $machine_default;
  }

  /**
   * Determines if the entity counter source ID already exists.
   *
   * @param string $source_id
   *   The entity counter source ID.
   *
   * @return bool
   *   TRUE if the entity counter source ID exists, FALSE otherwise.
   */
  public function exists($source_id) {
    $instance_ids = $this->getEntityCounter()->getSources()->getInstanceIds();
    return (isset($instance_ids[$source_id])) ? TRUE : FALSE;
  }

  /**
   * Get the entity counter source's entity counter.
   *
   * @return \Drupal\entity_counter\Entity\EntityCounterInterface
   *   The entity counter entity.
   */
  public function getEntityCounter() {
    return $this->entityCounter;
  }

  /**
   * Get the entity counter source's entity counter.
   *
   * @return \Drupal\entity_counter\Plugin\EntityCounterSourceInterface
   *   The entity counter source object.
   */
  public function getEntityCounterSource() {
    return $this->entityCounterSource;
  }

  /**
   * Process source form errors in form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $source_state
   *   The entity counter source form state.
   * @param \Drupal\Core\Form\FormStateInterface &$form_state
   *   The form state.
   */
  protected function processSourceFormErrors(FormStateInterface $source_state, FormStateInterface &$form_state) {
    foreach ($source_state->getErrors() as $name => $message) {
      $form_state->setErrorByName($name, $message);
    }
  }

}
