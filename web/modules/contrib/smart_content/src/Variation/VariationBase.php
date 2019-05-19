<?php

namespace Drupal\smart_content\Variation;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\smart_content\Condition\ConditionInterface;
use Drupal\smart_content\Condition\ConditionManager;
use Drupal\smart_content\Entity\SmartVariationSet;
use Drupal\smart_content\Form\SmartVariationSetForm;
use Drupal\smart_content\Reaction\ReactionInterface;
use Drupal\smart_content\Reaction\ReactionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;


/**
 * Base class for Smart variation plugins.
 */
abstract class VariationBase extends PluginBase implements VariationInterface, ConfigurablePluginInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  protected $weight;

  protected $conditionManager;

  protected $reactionManager;

  protected $conditions;

  protected $reactions;


  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.smart_content.condition'),
      $container->get('plugin.manager.smart_content.reaction')
    );
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConditionManager $condition_manager, ReactionManager $reaction_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->conditionManager = $condition_manager;
    $this->reactionManager = $reaction_manager;
  }

  public function id() {
    return isset($this->configuration['id']) ? $this->configuration['id'] : NULL;
  }

  public function setId($id) {
    $configuration = $this->getConfiguration();
    $configuration['id'] = $id;
    $this->setConfiguration($configuration);
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return [
      'id' => $this->id(),
      'plugin_id' => $this->getPluginId(),
      'weight' => $this->getWeight(),
    ];
  }

  /**
   * @inheritdoc
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
  }


  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  public function setWeight($weight) {
    $configuration = $this->getConfiguration();
    $configuration['weight'] = $weight;
    $this->setConfiguration($configuration);
  }

  public function getWeight() {
    return isset($this->configuration['weight']) ? $this->configuration['weight'] : 0;
  }

  /**
   * @param \Drupal\smart_content\Condition\ConditionInterface $condition
   */
  public function addCondition(ConditionInterface $condition) {
    if ($condition->id() === NULL) {
      $condition->setId(SmartVariationSet::generateUniquePluginId($condition, array_keys($this->getConditions())));
    }
    $this->conditions[$condition->id()] = $condition;

  }

  /**
   * @return array
   */
  public function getConditions() {
    if (!isset($this->conditions)) {
      $this->conditions = [];
      foreach ($this->getConditionsFromSettings() as $plugin) {
        $this->addCondition($plugin);
      }
    }
    return $this->conditions;
  }

  /**
   * @param $id
   *
   * @return mixed
   */
  public function getCondition($id) {
    foreach ($this->getConditions() as $condition) {
      if ($condition->id() == $id) {
        return $condition;
      }
    }
    return NULL;
  }

  /**
   * @param $id
   */
  public function removeCondition($id) {
    unset($this->conditions[$id]);
  }


  protected function getConditionsFromSettings() {
    $plugins = [];
    if (!empty($this->getConfiguration()['conditions_settings'])) {
      foreach ($this->getConfiguration()['conditions_settings'] as $id => $value) {
        $plugins[] = $this->conditionManager->createInstance($value['plugin_id'], $value);
      }
    }
    return $plugins;
  }


  public function sortConditions() {
    if ($this->getConditions()) {
      uasort($this->conditions, function ($first, $second) {
        return $first->getWeight() > $second->getWeight();
      });
    }
  }

  public function addReaction(ReactionInterface $reaction) {
    if ($reaction->id() === NULL) {
      $reaction->setId(SmartVariationSet::generateUniquePluginId($reaction, array_keys($this->getReactions())));
    }
    //@todo: find better way to do this.
    $this->reactions[$reaction->id()] = $reaction;

  }

  /**
   * @return array
   */
  public function getReactions() {
    if (!isset($this->reactions)) {
      $this->reactions = [];
      foreach ($this->getReactionsFromSettings() as $plugin) {
        $this->addReaction($plugin);
      }
    }
    return $this->reactions;
  }

  /**
   * @param $id
   *
   * @return mixed
   */
  public function getReaction($id) {
    foreach ($this->getReactions() as $reaction) {
      if ($reaction->id() == $id) {
        return $reaction;
      }
    }
    return NULL;
  }

  /**
   * @param $id
   */
  public function removeReaction($id) {
    unset($this->reactions[$id]);
  }


  protected function getReactionsFromSettings() {
    $plugins = [];
    if (!empty($this->getConfiguration()['reactions_settings'])) {
      foreach ($this->getConfiguration()['reactions_settings'] as $id => $value) {
        $plugins[] = $this->reactionManager->createInstance($value['plugin_id'], $value, $this->entity);
      }
    }
    return $plugins;
  }


  public function sortReactions() {
    if ($this->getReactions()) {
      uasort($this->reactions, function ($first, $second) {
        return $first->getWeight() > $second->getWeight();
      });
    }
  }

  public function writeChangesToConfiguration() {
    $configuration = $this->getConfiguration();
    $configuration['conditions_settings'] = [];
    foreach ($this->getConditions() as $condition) {
      $condition->writeChangesToConfiguration();
      $configuration['conditions_settings'][] = $condition->getConfiguration();
    }
    $configuration['reactions_settings'] = [];
    foreach ($this->getReactions() as $reaction) {
      $reaction->writeChangesToConfiguration();
      $configuration['reactions_settings'][] = $reaction->getConfiguration();
    }
    $this->setConfiguration($configuration);
  }


  public function getLibraries() {
    $libraries = [];

    foreach ($this->getConditions() as $condition) {
      $libraries = array_unique(array_merge($libraries, $condition->getLibraries()));
    }

    return $libraries;
  }


  public function getAttachedSettings() {
    $condition_settings = [];
    foreach ($this->getConditions() as $condition) {
      $condition_settings[] = $condition->getAttachedSettings();
    }
    return [
      'id' => $this->id(),
      'conditions' => $condition_settings,
    ];
  }


  function getResponse($context = []) {
    $response = new AjaxResponse();
    $content = [];
    foreach ($this->getReactions() as $reaction) {
      $reaction->buildResponse($response);
      if ($reaction_content = $reaction->getResponseContent($context)) {
        $content[] = $reaction_content;
      }
    }
    if (!empty($content)) {
      $response->addCommand(new ReplaceCommand($this->entity->getDecisionAgent()
        ->getResponseTarget(), $content));
    }
    return $response;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $wrapper_id = Html::getUniqueId('variation-wrapper');
    $wrapper_items_id = Html::getUniqueId('variation-items-wrapper');

    $form['conditions_config'] = [
      '#type' => 'container',
      '#title' => t('Condition(s)'),
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '-conditions' . '" class="conditions-container variation-conditions-container">',
      '#suffix' => '</div>',
    ];
    $form['conditions_config']['condition_items'] = [
      '#type' => 'table',
      '#header' => [t('Condition(s)'), t('Weight'), ''],
      '#prefix' => '<div id="' . $wrapper_items_id . '-conditions' . '" class="conditions-container-items variation-conditions-containers-items">',
      '#suffix' => '</div>',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $wrapper_items_id . '-order-condition-weight',
        ],
      ],
    ];
    foreach ($this->getConditions() as $condition_id => $condition) {
      if ($condition instanceof PluginFormInterface) {
        SmartVariationSetForm::pluginForm($condition, $form, $form_state, [
          'conditions_config',
          'condition_items',
          $condition_id,
          'plugin_form',
        ]);

        $form['conditions_config']['condition_items'][$condition_id]['plugin_form']['#type'] = 'container';
        $form['conditions_config']['condition_items'][$condition_id]['plugin_form']['#title'] = $condition->getPluginId();
        $form['conditions_config']['condition_items'][$condition_id]['plugin_form']['#attributes']['class'][] = 'condition';
        $form['conditions_config']['condition_items'][$condition_id]['#weight'] = $condition->getWeight();

        $form['conditions_config']['condition_items'][$condition_id]['#attributes']['class'][] = 'draggable';

        $form['conditions_config']['condition_items'][$condition_id]['weight'] = [
          '#type' => 'weight',
          '#title' => 'Weight',
          '#title_display' => 'invisible',
          '#default_value' => $condition->getWeight(),
          '#attributes' => ['class' => [$wrapper_items_id . '-order-condition-weight']],
        ];

        $form['conditions_config']['condition_items'][$condition_id]['remove_condition'] = [
          '#type' => 'submit',
          '#value' => t('Remove Condition'),
          '#name' => 'remove_condition_' . $this->id() . '__' . $condition_id,
          '#submit' => [[$this, 'removeElementCondition']],
          '#attributes' => ['class' => ['align-right', 'remove-condition', 'remove-button']],
          '#limit_validation_errors' => [],
        ];

        $form['conditions_config']['condition_items'][$condition_id]['remove_condition']['#ajax'] = [
          'callback' => [$this, 'removeElementConditionAjax'],
          'wrapper' => $wrapper_id . '-conditions',
        ];
      }
    }

    $form['conditions_config']['add_condition'] = [
      '#type' => 'container',
      '#title' => 'Add Condition',
      '#attributes' => ['class' => ['condition-add-container']],
      '#process' => [[$this, 'processConditionLimitValidation']],
    ];
    $form['conditions_config']['add_condition']['condition_type'] = [
      '#title' => 'Condition Type',
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => $this->conditionManager->getFormOptions(),
      '#empty_value' => '',
    ];
    $form['conditions_config']['add_condition']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Add Condition'),
      '#validate' => [[$this, 'addElementConditionValidate']],
      '#submit' => [[$this, 'addElementCondition']],
      '#name' => 'add_condition_' . $this->id(),
      '#ajax' => [
        'callback' => [$this, 'addElementConditionAjax'],
        'wrapper' => $wrapper_id . '-conditions',
      ],
    ];

    $form['reactions_config'] = [
      '#type' => 'container',
      '#title' => 'Reactions',
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '-reactions' . '">',
      '#suffix' => '</div>',
    ];

    $form['reactions_config']['reaction_items'] = [
      '#type' => 'table',
      '#header' => [t('Reaction(s)'), t('Weight'), ''],
      '#prefix' => '<div id="' . $wrapper_items_id . '-reactions' . '">',
      '#suffix' => '</div>',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $wrapper_items_id . '-order-reaction-weight',
        ],
      ],
    ];

    foreach ($this->getReactions() as $reaction_id => $reaction) {
      if ($reaction instanceof PluginFormInterface) {
        SmartVariationSetForm::pluginForm($reaction, $form, $form_state, [
          'reactions_config',
          'reaction_items',
          $reaction_id,
          'plugin_form',
        ]);

        $form['reactions_config']['reaction_items'][$reaction_id]['plugin_form']['#type'] = 'container';
        $form['reactions_config']['reaction_items'][$reaction_id]['plugin_form']['#title'] = $reaction->getPluginId();
        $form['reactions_config']['reaction_items'][$reaction_id]['plugin_form']['#attributes']['class'][] = 'reaction';
        $form['reactions_config']['reaction_items'][$reaction_id]['#weight'] = $reaction->getWeight();


        $form['reactions_config']['reaction_items'][$reaction_id]['#attributes']['class'][] = 'draggable';
        $form['reactions_config']['reaction_items'][$reaction_id]['#attributes']['class'][] = 'row-reaction';

        $form['reactions_config']['reaction_items'][$reaction_id]['weight'] = [
          '#type' => 'weight',
          '#title' => 'Weight',
          '#title_display' => 'invisible',
          '#default_value' => $reaction->getWeight(),
          '#attributes' => ['class' => [$wrapper_items_id . '-order-reaction-weight']],
        ];


        $form['reactions_config']['reaction_items'][$reaction_id]['remove_reaction'] = [
          '#type' => 'submit',
          '#value' => t('Remove Reaction'),
          '#name' => 'remove_reaction_' . $this->id() . '__' . $reaction_id,
          '#submit' => [[$this, 'removeElementReaction']],
          '#attributes' => ['class' => ['align-right', 'remove-reaction', 'remove-button']],
          '#limit_validation_errors' => [],
        ];
        $form['reactions_config']['reaction_items'][$reaction_id]['remove_reaction']['#ajax'] = [
          'callback' => [$this, 'removeElementReactionAjax'],
          'wrapper' => $wrapper_id . '-reactions',
        ];
      }
    }
    $form['reactions_config']['add_reaction'] = [
      '#type' => 'container',
    ];


    $form['reactions_config']['add_reaction'] = [
      '#type' => 'container',
      '#title' => 'Add Reaction',
    ];

    $form['reactions_config']['add_reaction']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Add Reaction'),
      '#submit' => [[$this, 'addElementReaction']],
      '#name' => 'add_reaction_' . $this->id(),
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'addElementReactionAjax'],
        'wrapper' => $wrapper_id . '-reactions',
      ],
    ];
    return $form;
  }

  /**
   * Render API callback: builds the formatter settings elements.
   */
  public function processConditionLimitValidation(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['submit']['#limit_validation_errors'] = [array_merge($element['#array_parents'], ['condition_type'])];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getConditions() as $condition_id => $condition) {
      SmartVariationSetForm::pluginFormValidate($condition, $form, $form_state, [
        'conditions_config',
        'condition_items',
        $condition_id,
        'plugin_form',
      ]);
    }
    foreach ($this->getReactions() as $reaction_id => $reaction) {
      if ($reaction instanceof PluginFormInterface) {
        SmartVariationSetForm::pluginFormValidate($reaction, $form, $form_state, [
          'reactions_config',
          'reaction_items',
          $reaction_id,
          'plugin_form',
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    self::attachTableConditionWeight($form_state->getValues()['conditions_config']['condition_items']);
    self::attachTableReactionWeight($form_state->getValues()['reactions_config']['reaction_items']);
    foreach ($this->getConditions() as $condition_id => $condition) {
      SmartVariationSetForm::pluginFormSubmit($condition, $form, $form_state, [
        'conditions_config',
        'condition_items',
        $condition_id,
        'plugin_form',
      ]);
    }
    foreach ($this->getReactions() as $reaction_id => $reaction) {
      if ($reaction instanceof PluginFormInterface) {
        SmartVariationSetForm::pluginFormSubmit($reaction, $form, $form_state, [
          'reactions_config',
          'reaction_items',
          $reaction_id,
          'plugin_form',
        ]);
      }
    }
  }

  public function attachTableConditionWeight($values) {
    foreach ($this->getConditions() as $condition) {
      if (isset($values[$condition->id()]['weight'])) {
        $condition->setWeight($values[$condition->id()]['weight']);
      }
    }
    $this->sortConditions();
  }

  public function addElementConditionValidate(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#parents'], 0, -1);
    $parents[] = 'condition_type';
    if (!$value = NestedArray::getValue($form_state->getUserInput(), $parents)) {
      $form_state->setError(NestedArray::getValue($form, $parents), 'Condition type required.');
    }
  }

  public function addElementCondition(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Save condition weight.
    $condition_values = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#parents'], 0, -2));
    if (isset($condition_values['condition_items'])) {
      $this->attachTableConditionWeight($condition_values['condition_items']);
    }

    $type = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#parents'], 0, -1))['condition_type'];
    $this->addCondition($this->conditionManager->createInstance($type));
    $form_state->setRebuild();
  }

  public function addElementConditionAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
  }

  public function removeElementCondition(array &$form, FormStateInterface $form_state) {

    $button = $form_state->getTriggeringElement();

    list($action, $name) = explode('__', $button['#name']);

    // Save condition weight.
    $condition_values = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#parents'], 0, -3));
    if (isset($condition_values['condition_items'])) {
      $this->attachTableConditionWeight($condition_values['condition_items']);
    }

    $variation = $this->entity->getVariation($this->id());
    $variation->removeCondition($name);
    $form_state->setRebuild();

  }

  public function removeElementConditionAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
  }

  public function attachTableReactionWeight($values) {
    foreach ($this->getReactions() as $reaction) {
      if (isset($values[$reaction->id()]['weight'])) {
        $reaction->setWeight($values[$reaction->id()]['weight']);
      }
    }
    $this->sortReactions();
  }

  public function addElementReaction(array &$form, FormStateInterface $form_state) {
    //@todo: reorder reactions to account for drupal core issue.
    $button = $form_state->getTriggeringElement();

    // Save reaction weight.
    $reaction_values = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#parents'], 0, -2));
    if (isset($reaction_values['reaction_items'])) {
      $this->attachTableReactionWeight($reaction_values['reaction_items']);
    }
    $this->addReaction($this->reactionManager->createInstance($this->getReactionPluginId(), [], $this->entity));
    $this->entity->addVariation($this);
    $form_state->setRebuild();
  }


  public function addElementReactionAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
  }

  public function removeElementReaction(array &$form, FormStateInterface $form_state) {

    $button = $form_state->getTriggeringElement();

    list($action, $name) = explode('__', $button['#name']);

    // Save reaction weight.
    $reaction_values = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#parents'], 0, -3));
    if (isset($reaction_values['reaction_items'])) {
      $this->attachTableReactionWeight($reaction_values['reaction_items']);
    }

    $variation = $this->entity->getVariation($this->id());
    $variation->removeReaction($name);
    $form_state->setRebuild();

  }

  public function removeElementReactionAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
  }

}

