<?php

namespace Drupal\switches\Form;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SwitchForm.
 */
class SwitchForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\switches\Entity\SwitchEntity
   */
  protected $entity;

  /**
   * The Condition plugin manager service.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The Context Repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * SwitchForm constructor.
   *
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The Condition plugin manager service.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The Context Repository service.
   */
  public function __construct(ConditionManager $condition_manager,
                              ContextRepositoryInterface $context_repository) {
    $this->conditionManager = $condition_manager;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.condition'),
      $container->get('context.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Store the gathered contexts in the form state for other objects to use
    // during form building.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    // Nest the form state values.
    $form['#tree'] = TRUE;

    $switch = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $switch->label(),
      '#description' => $this->t("Label for the Switch."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $switch->id(),
      '#machine_name' => [
        'exists' => '\Drupal\switches\Entity\SwitchEntity::load',
      ],
      '#disabled' => !$switch->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A summary of the intended purpose for this Switch.'),
      '#maxlength' => 255,
      '#default_value' => $switch->get('description'),
    ];

    $form['activationMethod'] = [
      '#type' => 'select',
      '#title' => $this->t('Activation Method'),
      '#options' => [
        'manual' => $this->t('Manual'),
        'condition' => $this->t('Condition'),
      ],
      '#description' => $this->t('Note: Manual Activation Methods are required for overrides.'),
      '#required' => TRUE,
      '#default_value' => $switch->getActivationMethod(),
    ];

    $form['manualActivationStatus'] = [
      '#type' => 'select',
      '#title' => $this->t('Manual Activation Status'),
      '#options' => [
        'true' => $this->t('True'),
        'false' => $this->t('False'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="activationMethod"]' => ['value' => 'manual'],
        ],
        'required' => [
          ':input[name="activationMethod"]' => ['value' => 'manual'],
        ],
      ],
      '#default_value' => $switch->getManualActivationStatus(),
    ];

    // Add the conditions interface to the form.
    $form['activation_conditions'] = $this->buildActivationConditionsInterface([], $form_state);

    // Add conditional display behavior to hide condition configuration.
    $form['activation_conditions']['#type'] = 'item';
    $form['activation_conditions']['#states'] = [
      'visible' => [
        ':input[name="activationMethod"]' => ['value' => 'condition'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Submit the separate conditions section as well.
    $this->submitActivationConditions($form, $form_state);

    // Save the conditions configuration.
    $this->entity->save();
  }

  /**
   * Helper function to independently submit the activation conditions UI.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function submitActivationConditions(array $form, FormStateInterface $form_state) {
    $activation_conditions = $form_state->getValue('activation_conditions');

    // Extract the enabled conditions tab from the front of the list.
    $enabled_conditions = array_shift($activation_conditions);

    // Filter down to only a list of selected plugins.
    $enabled_conditions = array_filter($enabled_conditions['conditions']);

    // Assign configuration for all enabled conditions.
    foreach ($activation_conditions as $condition_id => $values) {
      if (empty($enabled_conditions[$condition_id])) {
        // Ensure no plugin configuration is saved if the plugin is disabled.
        $this->entity->getActivationConditions()->removeInstanceId($condition_id);
      }
      else {
        // Allow the condition to submit the form.
        $condition = $form_state->get(['activationConditions', $condition_id]);
        $subform_state = SubformState::createForSubform($form['activation_conditions'][$condition_id], $form, $form_state);
        $condition->submitConfigurationForm($form['activation_conditions'][$condition_id], $subform_state);

        // Setting conditions' context mappings is the plugins' responsibility.
        // This code exists for backwards compatibility, because
        // \Drupal\Core\Condition\ConditionPluginBase::submitConfigurationForm()
        // did not set its own mappings until Drupal 8.2.
        // @todo Remove the code that sets context mappings in Drupal 9.0.0.
        if ($condition instanceof ContextAwarePluginInterface) {
          $context_mapping = isset($values['context_mapping']) ? $values['context_mapping'] : [];
          $condition->setContextMapping($context_mapping);
        }

        $condition_configuration = $condition->getConfiguration();
        // Update the activation conditions on the switch.
        $this->entity->getActivationConditions()
          ->addInstanceId($condition_id, $condition_configuration);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $switch = $this->entity;
    $status = $switch->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Switch.', [
          '%label' => $switch->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Switch.', [
          '%label' => $switch->label(),
        ]));
    }
    $form_state->setRedirectUrl($switch->toUrl('collection'));
  }

  /**
   * Prepare the activation conditions input form elements.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array containing the structure of the form.
   */
  protected function buildActivationConditionsInterface(array $form, FormStateInterface $form_state) {
    $form['activation_condition_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Activation conditions'),
      '#parents' => [
        'activation_condition_tabs',
      ],
    ];

    $condition_config = $this->entity->getActivationConditionsConfig();

    // Build an initial tab to select enabled conditions.
    $form['enabled_conditions'] = [
      '#type' => 'details',
      '#title' => $this->t('Enabled conditions'),
      '#group' => 'activation_condition_tabs',
      '#weight' => -99,
    ];

    $available_conditions = $this->getAvailableConditions($form, $form_state);
    $form['enabled_conditions']['conditions'] = [
      '#type' => 'checkboxes',
      '#title' => 'What conditions should be enabled for this switch?',
      '#options' => $this->getConditionOptions($available_conditions),
      '#default_value' => array_keys($condition_config),
    ];

    // Build and embed the plugin form for each condition plugin.
    foreach ($available_conditions as $condition_id => $definition) {
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $instance_config = isset($condition_config[$condition_id]) ? $condition_config[$condition_id] : [];
      $condition = $this->conditionManager->createInstance($condition_id, $instance_config);
      $form_state->set(['activationConditions', $condition_id], $condition);

      // Build the form section for this condition.
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition
        ->getPluginDefinition()['label'];
      $condition_form['#group'] = 'activation_condition_tabs';
      $form[$condition_id] = $condition_form;

      // Disable the form elements for each unselected condition plugin.
      // @todo Present some more prominent indicator to the editor.
      $form[$condition_id]['#states'] = [
        'disabled' => [
          ":input[name='activation_conditions[enabled_conditions][conditions][$condition_id]']" => ['checked' => FALSE],
        ],
      ];
    }

    return $form;
  }

  /**
   * Get option values for conditions plugin checkbox list.
   *
   * @param array $conditions
   *   An array of condition plugin definitions keyed by condition ID.
   *
   * @return array
   *   An array of checkbox options consisting of labels keyed by values.
   */
  protected function getConditionOptions(array $conditions) {
    $options = [];
    foreach ($conditions as $condition_id => $definition) {
      $options[$condition_id] = $definition['label'];
    }

    return $options;
  }

  /**
   * Get plugin definitions for all usable Condition plugins.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An array of condition plugin definitions usable with the contexts
   *   available to this form. Each definition is keyed by the plugin Id
   *   for each condition plugin.
   */
  protected function getAvailableConditions(array $form, FormStateInterface $form_state) {
    return $this->conditionManager->getDefinitionsForContexts($form_state
      ->getTemporaryValue('gathered_contexts'));
  }

}
