<?php

namespace Drupal\log_monitor\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\log_monitor\Condition\ConditionPluginManager;
use Drupal\log_monitor\Reaction\ReactionPluginManager;
use Drupal\log_monitor\Scheduler\SchedulerPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LogMonitorRuleForm.
 *
 * @package Drupal\log_monitor\Form
 */
class LogMonitorRuleForm extends EntityForm {
  protected $conditionManager;
  protected $reactionManager;

  protected $entity;
  /**
   * Class constructor.
   */
  public function __construct(SchedulerPluginManager $scheduleManager, ConditionPluginManager $conditionManager, ReactionPluginManager $reactionManager) {
    $this->scheduleManager = $scheduleManager;
    $this->conditionManager = $conditionManager;
    $this->reactionManager = $reactionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('plugin.manager.log_monitor.scheduler'),
      $container->get('plugin.manager.log_monitor.condition'),
      $container->get('plugin.manager.log_monitor.reaction')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if($entity = $form_state->get('log_monitor_rule')) {
       $this->entity = $entity;
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t("Label for the Log monitor rule."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\log_monitor\Entity\LogMonitorRule::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $this->buildSchedulerConfigForm($form, $form_state);

    $this->buildConditionConfigForm($form, $form_state);

    $this->buildReactionConfigForm($form, $form_state);

    return $form;
  }

  public function buildSchedulerConfigForm(array &$form, FormStateInterface $form_state) {
    $form['scheduler_config'] = [
      '#type' => 'container',
      '#title' => 'Schedule',
      '#prefix' => '<div id="scheduler-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    $scheduler_options = [];
    foreach($this->scheduleManager->getDefinitions() as $definition) {
      $scheduler_options[$definition['id']] = (string) $definition['title'];
     $descriptions[$definition['id']] = (string) $definition['description'];
    }

    $form['scheduler_config']['scheduler'] = [
      '#type' => 'radios',
      '#options' => $scheduler_options,
      '#ajax' => [
//        'callback' => [$this, 'addSchedulerAjax'],
        'callback' => [$this, 'addSchedulerAjax'],
        'wrapper' => 'scheduler-form-wrapper',
        'trigger_as' => array(
          'name' => 'add_scheduler',
        ),
      ],
      '#required' => TRUE,
    ];

    if($scheduler = $this->entity->getScheduler()) {
      $form['scheduler_config']['scheduler']['#default_value'] = $scheduler->getPluginId();
    }

    foreach($scheduler_options as $id => $description) {
      $form['scheduler_config']['scheduler'][$id]['#description'] = $descriptions[$id];
    }

    $form['scheduler_config']['scheduler_settings'] = [
      '#type' => 'container',
      '#title' => 'Schedule',
      '#prefix' => '<div id="scheduler-form-wrapper">',
      '#suffix' => '</div>',
    ];
    if($scheduler = $this->entity->getScheduler()) {
      if ($scheduler instanceof PluginFormInterface) {
        // Get the "sub-form state" and appropriate form part to send to
        // buildConfigurationForm().
        $scheduler_form = [];
        if (!empty($form['scheduler_config']['scheduler_settings']['plugin_form'])) {
          $scheduler_form = $form['scheduler_config']['scheduler_settings']['plugin_form'];
        }
        $scheduler_form_state = SubformState::createForSubform($scheduler_form, $form, $form_state);
        $scheduler_form = $scheduler->buildConfigurationForm($scheduler_form, $scheduler_form_state);

        $scheduler_form['#type'] = 'details';
        $scheduler_form['#title'] = $this->t('Settings for %pluginid', ['%pluginid' => $scheduler->getPluginId()]);
        $scheduler_form['#open'] = TRUE;
        $form['scheduler_config']['scheduler_settings']['plugin_form'] = $scheduler_form;
      }
    }

    $form['scheduler_config']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#submit' => [[$this, 'addScheduler']],
      '#name' => 'add_scheduler',
      '#ajax' => [
        'callback' => [$this, 'addSchedulerAjax'],
        'wrapper' => 'scheduler-form-wrapper',
      ],
      '#attributes' => [
        'class' => ['js-hide']
      ],
      '#limit_validation_errors' => [['scheduler_config', 'scheduler']]
    ];


  }
  public function addScheduler(array &$form, FormStateInterface $form_state) {
    $plugin_id = $form_state->getValues()['scheduler_config']['scheduler'];
    $this->entity->setScheduler($this->scheduleManager->createInstance($plugin_id));
    $form_state->set('log_monitor_rule', $this->entity);
    $form_state->setRebuild();
  }

  public function addSchedulerAjax(array &$form, FormStateInterface $form_state) {
    return $form['scheduler_config']['scheduler_settings'];
  }

  public function buildConditionConfigForm(array &$form, FormStateInterface $form_state) {
    $form['condition_config'] = [
      '#type' => 'container',
      '#title' => 'Conditions',
      '#prefix' => '<div id="conditions-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    $existing_condition_plugin_types = [];

    if($conditions = $this->entity->getConditions()) {

      $form['condition_config']['condition_items'] = [
        '#type' => 'fieldgroup',
        '#title' => 'Conditions',
        '#prefix' => '<div id="condition-items-wrapper">',
        '#suffix' => '</div>',
      ];


      foreach($conditions as $i => $condition) {
        if ($condition instanceof PluginFormInterface) {
          $existing_condition_plugin_types[$condition->getPluginId()] = $condition->getPluginId();
          // Get the "sub-form state" and appropriate form part to send to
          // buildConfigurationForm().
          $condition_form = [];
          if (!empty($form['condition_config']['condition_items'][$i]['plugin_form'])) {
            $condition_form = $form['condition_config']['condition_items'][$i]['plugin_form'];
          }
          $condition_form_state = SubformState::createForSubform($condition_form, $form, $form_state);
          $condition_form = $condition->buildConfigurationForm($condition_form, $condition_form_state);

          $condition_form['#type'] = 'details';
          $condition_form['#title'] = $this->t('%pluginid', ['%pluginid' => $condition->getPluginId()]);
          $condition_form['#open'] = $this->entity->isNew();
          $form['condition_config']['condition_items'][$i]['remove_condition'] = [
            '#type' => 'submit',
            '#value' => t('Remove Condition'),
            '#name' => 'remove_condition__' . $i,
            '#submit' => [[$this, 'removeCondition']],
            '#attributes' => ['class' => ['align-right']],
            '#ajax' => [
              'callback' => [$this, 'removeConditionAjax'],
              'wrapper' => 'conditions-wrapper',
            ],
            '#limit_validation_errors' => '',
          ];
          $form['condition_config']['condition_items'][$i]['plugin_form'] = $condition_form;

        }
      }
    }

    $add_condition_options = array_diff_key($this->conditionManager->getFormOptions(), $existing_condition_plugin_types);
    if($add_condition_options) {
      $form['condition_config']['add_condition'] = [
        '#type' => 'container',
        '#title' => 'Add Condition',
      ];

      $form['condition_config']['add_condition']['condition_type'] = [
        '#title' => 'Condition Type',
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#options' => $add_condition_options,
      ];

      $form['condition_config']['add_condition']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Add Condition'),
        '#submit' => [[$this, 'addCondition']],
        '#name' => 'add_condition',
        '#ajax' => [
          'callback' => array($this, 'addConditionAjax'),
          'wrapper' => 'conditions-wrapper'
        ],
        '#limit_validation_errors' => [['condition_config', 'add_condition', 'condition_type']]
      ];
    }
  }

  public function addCondition(array &$form, FormStateInterface $form_state) {
    $plugin_id = $form_state->getValues()['condition_config']['add_condition']['condition_type'];
    $this->entity->addCondition($this->conditionManager->createInstance($plugin_id));
    $form_state->set('log_monitor_rule', $this->entity);
    $form_state->setRebuild();
  }

  public function addConditionAjax(array &$form, FormStateInterface $form_state) {
    return $form['condition_config'];
  }

  public function removeCondition(array &$form, FormStateInterface $form_state) {
    list($action, $name) = explode('__', $form_state->getTriggeringElement()['#name']);
    $this->entity->removeCondition($name);
    $form_state->set('log_monitor_rule', $this->entity);
    $form_state->setRebuild();

  }

  public function removeConditionAjax(array &$form, FormStateInterface $form_state) {
    return $form['condition_config'];
  }

  public function buildReactionConfigForm(array &$form, FormStateInterface $form_state) {
    // Reactions
    $form['reaction_config'] = [
      '#type' => 'container',
      '#title' => 'Reactions',
      '#prefix' => '<div id="reactions-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];


    if($reactions = $this->entity->getReactions()) {

      $form['reaction_config']['reaction_items'] = [
        '#type' => 'fieldgroup',
        '#title' => 'Reactions',
        '#prefix' => '<div id="reaction-items-wrapper">',
        '#suffix' => '</div>',
      ];


      foreach($reactions as $i => $reaction) {
        if ($reaction instanceof PluginFormInterface) {
          // Get the "sub-form state" and appropriate form part to send to
          // buildConfigurationForm().
          $reaction_form = [];
          if (!empty($form['reaction_config']['reaction_items'][$i]['plugin_form'])) {
            $reaction_form = $form['reaction_config']['reaction_items'][$i]['plugin_form'];
          }
          $reaction_form_state = SubformState::createForSubform($reaction_form, $form, $form_state);
          $reaction_form = $reaction->buildConfigurationForm($reaction_form, $reaction_form_state);

          $reaction_form['#type'] = 'details';
          $reaction_form['#title'] = $this->t('%pluginid', ['%pluginid' => $reaction->getPluginId()]);
          $reaction_form['#open'] = $this->entity->isNew();
          $form['reaction_config']['reaction_items'][$i]['remove_reaction'] = [
            '#type' => 'submit',
            '#value' => t('Remove Reaction'),
            '#name' => 'remove_reaction__' . $i,
            '#submit' => [[$this, 'removeReaction']],
            '#attributes' => ['class' => ['align-right']],
            '#ajax' => [
              'callback' => [$this, 'removeReactionAjax'],
              'wrapper' => 'reactions-wrapper',
            ],
            '#limit_validation_errors' => '',
          ];
          $form['reaction_config']['reaction_items'][$i]['plugin_form'] = $reaction_form;

        }
      }
    }

    $form['reaction_config']['add_reaction'] = [
      '#type' => 'container',
      '#title' => 'Add Reaction',
    ];

    $form['reaction_config']['add_reaction']['reaction_type'] = [
      '#title' => 'Reaction Type',
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => $this->reactionManager->getFormOptions(),
    ];

    $form['reaction_config']['add_reaction']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Add Reaction'),
      '#submit' => [[$this, 'addReaction']],
      '#name' => 'add_reaction',
      '#ajax' => [
        'callback' => array($this, 'addReactionAjax'),
        'wrapper' => 'reactions-wrapper'
      ],
      '#limit_validation_errors' => [['reaction_config', 'add_reaction', 'reaction_type']]
    ];
  }
  // Reaction functions
  public function addReaction(array &$form, FormStateInterface $form_state) {
    $plugin_id = $form_state->getValues()['reaction_config']['add_reaction']['reaction_type'];
    $this->entity->addReaction($this->reactionManager->createInstance($plugin_id));
    $form_state->set('log_monitor_rule', $this->entity);
    $form_state->setRebuild();
  }

  public function addReactionAjax(array &$form, FormStateInterface $form_state) {
    return $form['reaction_config'];
  }

  public function removeReaction(array &$form, FormStateInterface $form_state) {
    list($action, $name) = explode('__', $form_state->getTriggeringElement()['#name']);
    $this->entity->removeReaction($name);
    $form_state->set('log_monitor_rule', $this->entity);
    $form_state->setRebuild();
  }

  public function removeReactionAjax(array &$form, FormStateInterface $form_state) {
    return $form['reaction_config'];
  }

  // Common functions
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach($this->entity->getConditions() as $i => $condition) {
      if ($condition instanceof PluginFormInterface) {
        $condition_form_state = SubformState::createForSubform($form['condition_config']['condition_items'][$i]['plugin_form'], $form, $form_state);
        $condition->submitConfigurationForm($form['condition_config']['condition_items'][$i]['plugin_form'], $condition_form_state);
      }
    }
    foreach($this->entity->getReactions() as $i => $reaction) {
      if ($reaction instanceof PluginFormInterface) {
        $reaction_form_state = SubformState::createForSubform($form['reaction_config']['reaction_items'][$i]['plugin_form'], $form, $form_state);
        $reaction->submitConfigurationForm($form['reaction_config']['reaction_items'][$i]['plugin_form'], $reaction_form_state);
      }
    }
    if($scheduler = $this->entity->getScheduler()) {
      if ($scheduler instanceof PluginFormInterface) {
        $scheduler_form_state = SubformState::createForSubform($form['scheduler_config']['scheduler_settings']['plugin_form'], $form, $form_state);
        $scheduler->submitConfigurationForm($form['scheduler_config']['scheduler_settings']['plugin_form'], $scheduler_form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $log_monitor_rule = $this->entity;

    $status = $log_monitor_rule->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Log monitor rule.', [
          '%label' => $log_monitor_rule->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Log monitor rule.', [
          '%label' => $log_monitor_rule->label(),
        ]));
    }
    $form_state->setRedirectUrl($log_monitor_rule->toUrl('collection'));
  }

}
