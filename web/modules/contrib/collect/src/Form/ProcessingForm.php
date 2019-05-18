<?php
/**
 * @file
 * Contains \Drupal\collect\Form\ProcessingForm.
 */

namespace Drupal\collect\Form;

use Drupal\collect\Processor\ProcessorInterface;
use Drupal\collect\Processor\ProcessorManagerInterface;
use Drupal\collect\Model\ModelInterface;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuring the processors of a model.
 */
class ProcessingForm extends EntityForm {

  /**
   * The injected Collect model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * The injected Collect processor plugin manager.
   *
   * @var \Drupal\collect\Processor\ProcessorManagerInterface
   */
  protected $processorManager;

  /**
   * The injected uuid generator service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Constructs a new ProcessingForm object.
   */
  public function __construct(ModelManagerInterface $model_manager, ProcessorManagerInterface $processor_manager, UuidInterface $uuid_service) {
    $this->modelManager = $model_manager;
    $this->processorManager = $processor_manager;
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.collect.model'),
      $container->get('plugin.manager.collect.processor'),
      $container->get('uuid')
    );
  }

  /**
   * Returns the title for the form.
   *
   * @param \Drupal\collect\Model\ModelInterface $collect_model
   *   The model being configured.
   *
   * @return string
   *   The form title.
   */
  public function title(ModelInterface $collect_model) {
    return $this->t('Edit %model processing', ['%model' => $collect_model->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collect_processing_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $processors = array_map(function(array $definition) {
      return $definition['label'];
    }, $this->processorManager->getDefinitions());
    $description = $processors ? $this->t('Select a processor plugin to add it to the model.') : $this->t('No processor plugins installed. You have to enable a module that provides one for it to appear in the list.');

    $form['processor_add_select'] = array(
      '#type' => 'select',
      '#title' => $this->t('Add processor'),
      '#options' => $processors,
      '#empty_option' => $this->t('- Select -'),
      '#disabled' => !$processors,
      '#description' => $description,
    );

    $form['processor_add_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#submit' => ['::submitAddProcessor'],
      '#limit_validation_errors' => [['processor_add_select']],
      '#ajax' => array(
        'wrapper' => 'processors-table-wrapper',
        'callback' => array($this, 'replaceProcessorsTable'),
        'method' => 'replace',
      ),
      '#disabled' => !$processors,
    );

    $form['processors'] = array(
      '#type' => 'table',
      '#header' => [NULL, $this->t('Processor'), $this->t('Weight'), $this->t('Description'), $this->t('Settings'), $this->t('Operations')],
      '#empty' => $this->t('There are no processors yet.'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'processor-weight',
        ),
      ),
      '#prefix' => '<div id="processors-table-wrapper">',
      '#suffix' => '</div>',
    );

    // Processor weight counter.
    $count = 0;
    foreach ($this->getEntity()->getProcessorsPluginCollection() as $key => $processor) {
      /** @var \Drupal\collect\Processor\ProcessorInterface $processor */
      $processor_sub_form = $this->doWithSubFormState($form, $form_state, $key, [$processor, 'buildConfigurationForm']);

      $form['processors'][$key] = array(
        '#attributes' => ['class' => ['draggable']],
        '#weight' => $processor->getWeight(),
        'plugin_id' => array(
          '#type' => 'value',
          '#value' => $processor->getPluginId(),
        ),
        'label' => ['#markup' => $processor->label()],
        'weight' => array(
          '#type' => 'weight',
          '#title' => $this->t('Weight for @label', ['@label' => $processor->label()]),
          '#title_display' => 'invisible',
          '#default_value' => $processor->getWeight(),
          '#attributes' => array(
            'class' => array('processor-weight'),
          ),
        ),
        'description' => ['#markup' => $processor->getDescription()],
        'settings' => $processor_sub_form,
        'remove' => array(
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_' . $key,
          '#submit' => ['::submitRemoveProcessor'],
          '#limit_validation_errors' => [],
          '#ajax' => array(
            'wrapper' => 'processors-table-wrapper',
            'callback' => array($this, 'replaceProcessorsTable'),
            'method' => 'replace',
          ),
        ),
      );
      $count++;
    }
    // Add weight to the form state.
    $form_state->set('weight', $count);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // Hide Delete button.
    $actions['delete']['#access'] = FALSE;
    return $actions;
  }

  /**
   * Submit handler for the processor "Add" button.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function submitAddProcessor(array &$form, FormStateInterface $form_state) {
    // Add the selected processor to the config.
    $this->getEntity()->getProcessorsPluginCollection()
      ->addInstanceId($this->uuidService->generate(), [
        'plugin_id' => $form_state->getValue('processor_add_select'),
        'weight' => $form_state->get('weight'),
      ]);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the processor "Remove" button.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function submitRemoveProcessor(array &$form, FormStateInterface $form_state) {
    $submit_name = $form_state->getTriggeringElement()['#name'];
    $key = substr($submit_name, strlen('remove_'));

    // Remove given processor from entity.
    $this->getEntity()->getProcessorsPluginCollection()->removeInstanceId($key);

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Trigger processor plugin form validation.
    foreach ($this->getEntity()->getProcessorsPluginCollection() as $key => $processor) {
      $this->doWithSubFormState($form, $form_state, $key, [$processor, 'validateConfigurationForm']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Trigger processor plugin form submit handler.
    foreach ($this->getEntity()->getProcessorsPluginCollection() as $key => $processor) {
      $this->doWithSubFormState($form, $form_state, $key, [$processor, 'submitConfigurationForm']);
      // Weights are not controlled by processor. Update them here.
      $processor->setWeight($form_state->getValue(['processors', $key, 'weight']));
    }
    $this->getEntity()->getProcessorsPluginCollection()->sort();

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The processing has been saved.'));
    return parent::save($form, $form_state);
  }

  /**
   * Wrapper for calling processor form methods with partial form state.
   *
   * @param array $form
   *   The main form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The main form state.
   * @param string $key
   *   The key identifying the processor in the form structure.
   * @param callable $callback
   *   A callback to invoke with the generated partial form and form state.
   *
   * @return mixed
   *   The return value of the callback.
   */
  protected function doWithSubFormState(array $form, FormStateInterface $form_state, $key, callable $callback) {
    $parents = ['processors', $key, 'settings'];

    $sub_form = NestedArray::getValue($form, $parents) ?: array();
    $sub_form_state = (new FormState())
      ->setLimitValidationErrors($form_state->getLimitValidationErrors())
      ->setTriggeringElement($form_state->getTriggeringElement())
      ->setRebuild($form_state->isRebuilding())
      ->setValues($form_state->getValue($parents) ?: array());

    $return_value = $callback($sub_form, $sub_form_state);

    // Update form state with potentially changed values.
    $form_state
      ->setLimitValidationErrors($sub_form_state->getLimitValidationErrors())
      ->setTriggeringElement($sub_form_state->getTriggeringElement())
      ->setRebuild($sub_form_state->isRebuilding())
      ->setValue($parents, $sub_form_state->getValues() ?: array());
    foreach ($sub_form_state->getErrors() as $name => $error) {
      $form_state->setErrorByName($name, $error);
    }

    return $return_value;
  }

  /**
   * Returns the processors table from the form.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The processors table as a renderable array.
   */
  public function replaceProcessorsTable(array &$form, FormStateInterface $form_state) {
    return $form['processors'];
  }

  /**
   * Returns the model that the processing belongs to.
   *
   * @return \Drupal\collect\Model\ModelInterface
   *   The current form entity.
   */
  public function getEntity() {
    // Override only to modify documentation and typehint.
    return parent::getEntity();
  }

}
