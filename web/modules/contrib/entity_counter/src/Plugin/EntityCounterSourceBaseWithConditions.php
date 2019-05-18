<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\Php as UuidGenerator;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element\Fieldset;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for an entity counter source with conditions.
 *
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceInterface
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceManagerInterface
 * @see plugin_api
 */
abstract class EntityCounterSourceBaseWithConditions extends EntityCounterSourceBase implements EntityCounterSourceWithConditionsInterface {

  use ConditionAccessResolverTrait;

  /**
   * The plugin collection that holds the conditions.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection
   */
  protected $conditionCollection;

  /**
   * The data objects representing the context of this plugin.
   *
   * @var \Drupal\Component\Plugin\Context\ContextInterface[]
   */
  protected $context = [];

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuidGenerator;

  /**
   * EntityCounterSourceBaseWithConditions constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current active user.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition plugin manager.
   * @param \Drupal\Component\Uuid\Php $uuid_generator
   *   The UUID generator.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The plugin context handler.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context manager service.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   *
   * @see \Drupal\entity_counter\Entity\EntityCounter::getSources
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $account, ConditionManager $condition_manager, UuidGenerator $uuid_generator, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository, LanguageManager $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $account);

    $this->conditionManager = $condition_manager;
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
    $this->languageManager = $language_manager;
    $this->uuidGenerator = $uuid_generator;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.manager.condition'),
      $container->get('uuid'),
      $container->get('context.handler'),
      $container->get('context.repository'),
      $container->get('language_manager')
    );
  }

  /**
   * Callback #pre_render for conditions_form form element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the form
   *   element.
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderForm(array $element) {
    foreach (array_keys($element['items']['#rows']) as $row) {
      $element['items']['#rows'][$row]['operations']['data'] = [
        'edit_condition' => $element['buttons'][$row]['edit_condition'],
        'remove_condition' => $element['buttons'][$row]['remove_condition'],
      ];
    }
    unset($element['buttons']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // The configuration of the conditions.
      'conditions' => [],
      // Tracks the logic used, either 'and' or 'or'.
      'conditions_logic' => 'and',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form_state);
    // Store the gathered contexts in the form state for other objects to use
    // during form building.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    $form['conditions_form'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Conditions'),
      '#weight' => 90,
      '#attributes' => [
        'id' => ['entity-counter-source-conditions-wrapper'],
      ],
      '#pre_render' => [
        [Fieldset::class, 'preRenderGroup'],
        [$this, 'preRenderForm'],
      ],
    ];
    $form['conditions_form']['condition_options'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];
    $form['conditions_form']['condition_options']['conditions_logic'] = [
      '#type' => 'select',
      '#options' => [
        'and' => $this->t('@logic (For all conditions)', ['@logic' => $this->t('AND')]),
        'or' => $this->t('@logic (For all conditions)', ['@logic' => $this->t('OR')]),
      ],
      '#default_value' => mb_strtolower($this->getConditionsLogic()),
      '#parents' => [
        'settings',
        'conditions_logic',
      ],
    ];
    $form['conditions_form']['condition_options']['condition_selector'] = [
      '#type' => 'select',
      '#options' => $this->getContextOptionsForm(),
    ];
    $form['conditions_form']['condition_options']['add'] = [
      '#type' => 'submit',
      '#name' => 'add_condition',
      '#value' => $this->t('Add Condition'),
      '#submit' => [[$this, 'submitConditionForm']],
      '#ajax' => [
        'callback' => [$this, 'addConditionForm'],
        'wrapper' => 'entity-counter-source-conditions-wrapper',
        'effect' => 'fade',
      ],
    ];

    if ($form_state->isRebuilding() && ($submit_element = $form_state->getTriggeringElement()) !== NULL &&
      ($submit_element['#name'] == 'add_condition' || strpos($submit_element['#name'], 'edit_condition-') === 0)) {
      $subform['condition_add_form'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Add condition'),
      ];

      if ($submit_element['#name'] == 'add_condition') {
        $condition_plugin = NestedArray::getValue($form_state->getValues(), array_merge(array_slice($submit_element['#array_parents'], 1, -1), ['condition_selector']));
        $instance = $this->conditionManager->createInstance($condition_plugin);
      }
      else {
        $condition_id = preg_replace('/^edit_condition-/', '', $submit_element['#name']);
        $instance = $this->getCondition($condition_id);
        $condition_plugin = $instance->getPluginId();
      }

      $subform_state = SubformState::createForSubform($subform['condition_add_form'], $form, $form_state);
      $form['conditions_form'][$condition_plugin] = $instance->buildConfigurationForm($subform['condition_add_form'], $subform_state);
      $form['conditions_form'][$condition_plugin]['instance'] = [
        '#type' => 'value',
        '#value' => $instance,
        '#parents' => ['plugin_instance'],
      ];
      $form['conditions_form'][$condition_plugin]['submit_condition'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#name' => ($submit_element['#name'] == 'add_condition') ? 'submit_condition' : 'update_condition-' . $condition_id,
        '#value' => ($submit_element['#name'] == 'add_condition') ? $this->t('Add') : $this->t('Update'),
        '#submit' => [[$this, 'submitConditionForm']],
        '#ajax' => [
          'callback' => [$this, 'addConditionForm'],
          'wrapper' => 'entity-counter-source-conditions-wrapper',
          'effect' => 'fade',
        ],
      ];
      $form['conditions_form'][$condition_plugin]['cancel_condition'] = [
        '#type' => 'submit',
        '#name' => 'cancel_condition',
        '#value' => $this->t('Cancel'),
        '#limit_validation_errors' => [],
        '#submit' => [[$this, 'submitConditionForm']],
        '#ajax' => [
          'callback' => [$this, 'addConditionForm'],
          'wrapper' => 'entity-counter-source-conditions-wrapper',
          'effect' => 'fade',
        ],
      ];
    }
    $form['conditions'] = [
      '#type' => 'value',
      '#value' => $this->getConfiguration()['settings']['conditions'],
      '#parents' => [
        'settings',
        'conditions',
      ],
    ];
    $form['conditions_form']['items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Plugin Id'),
        $this->t('Summary'),
        $this->t('Operations'),
      ],
      '#rows' => $this->renderRows(),
      '#empty' => $this->t('No conditions have been configured.'),
    ];

    // The operations render array is inside a render attribute in the items
    // table and render attributes are not processed as render element children.
    // That is the reason #ajax does not work inside the table.
    // We use a pre_render callback to move this elements inside the table.
    // @see self::preRenderForm().
    foreach ($this->getConditions() as $row => $condition) {
      $form['conditions_form']['buttons'][$row] = [
        'edit_condition' => [
          '#type' => 'submit',
          '#id' => 'edit-condition-' . $row,
          '#name' => 'edit_condition-' . $row,
          '#value' => $this->t('Edit'),
          '#submit' => [[$this, 'submitConditionForm']],
          '#ajax' => [
            'callback' => [$this, 'editConditionForm'],
            'wrapper' => 'entity-counter-source-conditions-wrapper',
            'effect' => 'fade',
          ],
        ],
        'remove_condition' => [
          '#type' => 'submit',
          '#id' => 'remove-condition-' . $row,
          '#name' => 'remove_condition-' . $row,
          '#value' => $this->t('Remove'),
          '#submit' => [[$this, 'submitConditionForm']],
          '#ajax' => [
            'callback' => [$this, 'editConditionForm'],
            'wrapper' => 'entity-counter-source-conditions-wrapper',
            'effect' => 'fade',
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The updated element.
   */
  public function addConditionForm(array $form, FormStateInterface &$form_state) {
    $submit_element = $form_state->getTriggeringElement();

    $conditions_form = NestedArray::getValue($form, array_slice($submit_element['#array_parents'], 0, -2));

    return $conditions_form;
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The updated element.
   */
  public function editConditionForm(array $form, FormStateInterface &$form_state) {
    $submit_element = $form_state->getTriggeringElement();

    $conditions_form = NestedArray::getValue($form, array_slice($submit_element['#array_parents'], 0, -3));

    return $conditions_form;
  }

  /**
   * Form submission handler for adding another condition.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitConditionForm(array $form, FormStateInterface &$form_state) {
    $submit_element = $form_state->getTriggeringElement();

    if ($submit_element['#name'] == 'submit_condition' ||
      strpos($submit_element['#name'], 'update_condition-') === 0) {
      // If it is an update operation first remove it.
      if (strpos($submit_element['#name'], 'update_condition-') === 0) {
        $condition_id = preg_replace('/^update_condition-/', '', $submit_element['#name']);
        $this->removeCondition($condition_id);
      }

      $parents = array_slice($submit_element['#array_parents'], 0, -1);
      $add_condition = NestedArray::getValue($form, $parents);

      $subform_state = SubformState::createForSubform($add_condition, $form, $form_state);
      $plugin_instance = $form_state->getValue('plugin_instance');
      if ($plugin_instance instanceof ContextAwarePluginInterface) {
        /** @var \Drupal\Core\Plugin\ContextAwarePluginInterface $plugin_instance */
        $context_mapping = $form_state->hasValue('context_mapping') ? $form_state->getValue('context_mapping') : [];
        $plugin_instance->setContextMapping($context_mapping);
      }

      // Submit plugin configuration.
      $plugin_instance->submitConfigurationForm($form, $subform_state);

      $values = $subform_state->getValues();
      $values['id'] = $plugin_instance->getPluginId();
      unset($values['submit_condition'], $values['cancel_condition']);
      $condition = $this->addCondition($values);

      // Update the original form values.
      $settings = $form_state->getValues();
      $settings['settings']['conditions'][$condition] = $values;
      if (!empty($condition_id)) {
        // Remove old condition id.
        unset($settings['settings']['conditions'][$condition_id]);
      }
      $form_state->setValues($settings);
    }
    elseif (strpos($submit_element['#name'], 'remove_condition-') === 0) {
      $condition_id = preg_replace('/^remove_condition-/', '', $submit_element['#name']);

      $this->removeCondition($condition_id);

      // Update the original form values.
      $settings = $form_state->getValues();
      unset($settings['settings']['conditions'][$condition_id]);
      $form_state->setValues($settings);
    }

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $submit_element = $form_state->getTriggeringElement();

    if ($submit_element['#name'] == 'submit_condition' ||
      strpos($submit_element['#name'], 'update_condition-') === 0) {
      $parents = array_slice($submit_element['#array_parents'], 0, -1);
      $add_condition = NestedArray::getValue($form, $parents);

      $subform_state = SubformState::createForSubform($add_condition, $form, $form_state);
      $plugin_instance = $form_state->getValue('plugin_instance');
      if (empty($plugin_instance)) {
        $plugin_instance = $this->conditionManager->createInstance(end($parents));
      }
      if ($plugin_instance instanceof ContextAwarePluginInterface) {
        /** @var \Drupal\Core\Plugin\ContextAwarePluginInterface $plugin_instance */
        $context_mapping = $form_state->hasValue('context_mapping') ? $form_state->getValue('context_mapping') : [];
        $plugin_instance->setContextMapping($context_mapping);
      }

      $plugin_instance->validateConfigurationForm($form, $subform_state);

      // Process source form state errors.
      $this->processConditionFormErrors($subform_state, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form_state);
    $form_state->setValues($this->getConfiguration()['settings']);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'conditions' => $this->getConditions(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluateConditions() {
    // Entity counter sources without conditions always apply.
    if (!$this->getConditions()->count()) {
      return TRUE;
    }

    $conditions = [];
    $missing_context = FALSE;
    foreach ($this->getConditions() as $condition_id => $condition) {
      if ($condition instanceof ContextAwarePluginInterface) {
        try {
          foreach ($this->getConditionsContext() as $name => $value) {
            $condition->setContextValue($name, $value);
          }
          $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
          $this->contextHandler->applyContextMapping($condition, $contexts);
        }
        catch (ContextException $exception) {
          $missing_context = TRUE;
        }
        catch (PluginException $exception) {
          watchdog_exception('entity_counter', $exception);
        }
      }
      $conditions[$condition_id] = $condition;
    }

    if (!$missing_context && $this->resolveConditions($conditions, $this->getConditionsLogic()) !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    if (!$this->conditionCollection) {
      $this->conditionCollection = new ConditionPluginCollection($this->conditionManager, $this->configuration['conditions']);
    }

    return $this->conditionCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionsContext() {
    return $this->context;
  }

  /**
   * {@inheritdoc}
   */
  public function setConditionsContext(array $contexts) {
    $this->context = $contexts;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCondition(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator->generate();
    $this->getConditions()->addInstanceId($configuration['uuid'], $configuration);

    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCondition($condition_id) {
    return $this->getConditions()->get($condition_id);
  }

  /**
   * {@inheritdoc}
   */
  public function removeCondition($condition_id) {
    $this->getConditions()->removeInstanceId($condition_id);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionsLogic() {
    return $this->configuration['conditions_logic'];
  }

  /**
   * {@inheritdoc}
   */
  public function setConditionsLogic($logic) {
    $this->configuration['conditions_logic'] = $logic;

    return $this;
  }

  /**
   * Returns an array of contexts.
   *
   * @return array
   *   An array of key value pairs suitable as '#options' for form elements.
   */
  protected function getContextOptionsForm() {
    static $options = NULL;

    if ($options === NULL) {
      $contexts = $this->contextRepository->getAvailableContexts();
      foreach ($this->conditionManager->getDefinitionsForContexts($contexts) as $plugin_id => $definition) {
        // Don't display the language condition until we have multiple
        // languages.
        if ($plugin_id == 'language' && !$this->languageManager->isMultilingual()) {
          continue;
        }

        $options[$plugin_id] = (string) $definition['label'];
      }
    }

    return $options;
  }

  /**
   * Process condition form errors in form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $condition_state
   *   The entity counter source form state.
   * @param \Drupal\Core\Form\FormStateInterface &$form_state
   *   The form state.
   */
  protected function processConditionFormErrors(FormStateInterface $condition_state, FormStateInterface &$form_state) {
    foreach ($condition_state->getErrors() as $name => $message) {
      $form_state->setErrorByName($name, $message);
    }
  }

  /**
   * Renders the conditions rows.
   *
   * @return array
   *   The conditions render array.
   */
  protected function renderRows() {
    $configured_conditions = [];
    foreach ($this->getConditions() as $row => $condition) {
      $configured_conditions[$row] = [
        $condition->getPluginId(),
        $condition->summary(),
        // @codingStandardsIgnoreStart
        'operations' => [],
        // @codingStandardsIgnoreEnd
      ];
    }

    return $configured_conditions;
  }

}
