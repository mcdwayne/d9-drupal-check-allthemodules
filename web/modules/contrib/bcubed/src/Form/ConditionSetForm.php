<?php

namespace Drupal\bcubed\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConditionSetForm.
 *
 * @package Drupal\bcubed\Form
 */
class ConditionSetForm extends EntityForm {

  /**
   * Bcubed Event Plugin Manager.
   *
   * @var \Drupal\bcubed\EventManager
   */
  protected $eventManager;

  /**
   * Bcubed Action Plugin Manager.
   *
   * @var \Drupal\bcubed\ActionManager
   */
  protected $actionManager;

  /**
   * Bcubed Condition Plugin Manager.
   *
   * @var \Drupal\bcubed\ConditionManager
   */
  protected $conditionManager;

  /**
   * Constructs a new ConditionSetForm object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $event_manager
   *   Bcubed Event Plugin Manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $action_manager
   *   Bcubed Action Plugin Manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $condition_manager
   *   Bcubed Condition Plugin Manager.
   */
  public function __construct(PluginManagerInterface $event_manager, PluginManagerInterface $action_manager, PluginManagerInterface $condition_manager) {
    $this->eventManager = $event_manager;
    $this->actionManager = $action_manager;
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.bcubed.event'),
      $container->get('plugin.manager.bcubed.action'),
      $container->get('plugin.manager.bcubed.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;

    $condition_set = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $condition_set->label(),
      '#description' => $this->t("Label for the Condition Set."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $condition_set->id(),
      '#machine_name' => [
        'exists' => '\Drupal\bcubed\Entity\ConditionSet::load',
      ],
      '#disabled' => !$condition_set->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Brief description of this condition set, shown in the condition sets overview.'),
      '#default_value' => $condition_set->get('description'),
    ];

    $form['plugins'] = [
      '#type' => 'container',
      '#prefix' => '<div id="bcubed-plugins-div">',
      '#suffix' => '</div>',
    ];

    $this->buildPluginForm($this->eventManager, 'Event', $condition_set, $form, $form_state);

    $this->buildPluginForm($this->conditionManager, 'Condition', $condition_set, $form, $form_state);

    $this->buildPluginForm($this->actionManager, 'Action', $condition_set, $form, $form_state);

    $this->removePluginsWithUnmetDependencies($form, $form_state);

    return $form;
  }

  /**
   * Builds a form for the specified plugin.
   */
  public function buildPluginForm($pluginManager, $plugin_type, $condition_set, &$form, &$form_state) {
    $form['plugins'][$plugin_type] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $plugin_type . 's',
      '#prefix' => '<div id="' . $plugin_type . '-div">',
      '#suffix' => '</div>',
    ];

    $current_plugins = $form_state->get('current_' . $plugin_type);

    // Fetch plugin definitions.
    $plugin_defs = $pluginManager->getDefinitions();

    // Build default plugins from config.
    if (is_null($current_plugins)) {
      $current_plugins = [];
      $pluginsettings = [];
      $existing_plugins = $condition_set->get(strtolower($plugin_type) . 's');
      if (!empty($existing_plugins)) {
        foreach ($existing_plugins as $plugin) {
          $current_plugins[] = $plugin['id'];
          if (!empty($plugin['data'])) {
            // Set existing plugin settings.
            $pluginsettings[] = $plugin['data'];
          }
          else {
            $pluginsettings[] = FALSE;
          }
        }
      }
      $form_state->set('current_' . $plugin_type, $current_plugins);
      $form_state->set($plugin_type . '_settings', $pluginsettings);
    }

    // Add new plugin if a selection has been made.
    $selected_plugin = $form_state->getValue(['plugins', $plugin_type, 'add'], 'none');
    if (!empty($selected_plugin) && $selected_plugin !== 'none') {
      $current_plugins = $form_state->get('current_' . $plugin_type);
      $current_plugins[] = $selected_plugin;
      $form_state->set('current_' . $plugin_type, $current_plugins);
      $pluginsettings = $form_state->get($plugin_type . '_settings');
      $pluginsettings[] = FALSE;
      $form_state->set($plugin_type . '_settings', $pluginsettings);
      $form_state->set('new_' . $plugin_type, count($current_plugins) - 1);
    }

    $avail_plugins = [];
    // Build available plugins based on which definitions are unused / support multiple instances.
    foreach ($plugin_defs as $plugin_id => $plugin_def) {
      if (!in_array($plugin_id, $current_plugins) || (!empty($plugin_def['instances']))) {
        $avail_plugins[$plugin_id] = $plugin_def['label'];
      }
    }

    if (!empty($avail_plugins)) {
      $form['plugins'][$plugin_type]['add'] = [
        '#weight' => 100,
        '#type' => 'select',
        '#options' => $avail_plugins,
        '#empty_option' => 'Add ' . $plugin_type,
        '#empty_value' => 'none',
        '#ajax' => [
          'callback' => [$this, 'ajaxAddPlugin'],
          'wrapper' => 'bcubed-plugins-div',
          'event' => 'change',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];
    }

    $pluginsettings = $form_state->get($plugin_type . '_settings');

    // Add plugins to form.
    for ($i = 0, $size = count($current_plugins); $i < $size; ++$i) {
      try {
        $plugin_id = $current_plugins[$i];
        // Fetch plugin instance.
        if ($pluginsettings[$i]) {
          // Create pre-configured instance.
          $plugin = $pluginManager->createInstance($plugin_id, ['settings' => $pluginsettings[$i]]);
        }
        else {
          // Create instance without configuration.
          $plugin = $pluginManager->createInstance($plugin_id);
        }
        $settings_form = [
          '#parents' => ['plugins', $plugin_type, $i],
          '#tree' => TRUE,
        ];
        $settings_form = $plugin->settingsForm($settings_form, $form_state);

        if (is_null($settings_form)) {
          $settings_form = [
            '#markup' => '<span>This ' . $plugin_type . ' has no configurable settings</span>',
            // Neccessary for obtaining a proper user input array.
            'hidden' => ['#type' => 'hidden', '#value' => $i],
          ];
        }

        // Fix #states properties of inserted form.
        foreach ($settings_form as $itemkey => $item) {
          if (isset($item['#states'])) {
            foreach ($item['#states'] as $state => $sval) {
              foreach ($sval as $key => $value) {
                if (preg_match('/name="(.*)"/', $key, $matches)) {
                  $key = str_replace($matches[1], 'plugins[' . $plugin_type . '][' . $i . '][' . $matches[1] . ']', $key);
                  $settings_form[$itemkey]['#states'][$state] = [$key => $value];
                }
              }
            }
          }
        }

        $plugin_details = [
          '#type' => 'details',
          '#title' => $plugin_defs[$plugin_id]['label'],
        ];

        $plugin_details['remove'] = [
          '#weight' => 100,
          '#type' => 'submit',
          '#name' => 'remove_' . $plugin_type . $i,
          '#value' => 'Remove ' . $plugin_type,
          '#limit_validation_errors' => [],
          '#submit' => ['::removePlugin'],
          '#ajax' => [
            'callback' => [$this, 'ajaxRemovePlugin'],
            'wrapper' => 'bcubed-plugins-div',
            'effect' => 'fade',
            'progress' => [
              'type' => 'throbber',
            ],
          ],
        ];

        $plugin_details['settings'] = $settings_form;
        $form['plugins'][$plugin_type][$i] = $plugin_details;
      }
      catch (PluginNotFoundException $e) {
        // Handle missing plugin.
      }

    }
    return $form;
  }

  /**
   * Removes plugins with unmet dependencies.
   */
  public function removePluginsWithUnmetDependencies(&$form, &$form_state) {
    // Fetch all existing condition sets.
    $existing_condition_sets = $this->entity->loadMultiple();
    // Exclude current condition set from list if it exists (values will be added back in from formstate)
    if (!is_null($this->entity->id())) {
      unset($existing_condition_sets[$this->entity->id()]);
    }

    $event_defs = $this->eventManager->getDefinitions();
    $condition_defs = $this->conditionManager->getDefinitions();
    $action_defs = $this->actionManager->getDefinitions();

    // Build array of active plugin definitions in all other sets.
    $other_plugins = [
      'event' => [],
      'action' => [],
      'condition' => [],
    ];

    foreach ($existing_condition_sets as $set) {
      $events = $set->get('events');
      foreach ($events as $event) {
        if (!isset($other_plugins['event'][$event['id']])) {
          $other_plugins['event'][$event['id']] = $event_defs[$event['id']];
        }
      }
      $actions = $set->get('actions');
      foreach ($actions as $action) {
        if (!isset($other_plugins['action'][$action['id']])) {
          $other_plugins['action'][$action['id']] = $action_defs[$action['id']];
        }
      }
      $conditions = $set->get('conditions');
      foreach ($conditions as $condition) {
        if (!isset($other_plugins['condition'][$condition['id']])) {
          $other_plugins['condition'][$condition['id']] = $condition_defs[$condition['id']];
        }
      }
    }

    // Build array of active plugin definitions in this set.
    $current_plugins = [
      'event' => [],
      'action' => [],
      'condition' => [],
    ];

    if (!empty($form_state->get('current_Event'))) {
      foreach ($form_state->get('current_Event') as $id) {
        if (!isset($current_plugins['event'][$id])) {
          $current_plugins['event'][$id] = $event_defs[$id];
        }
      }
    }
    if (!empty($form_state->get('current_Action'))) {
      foreach ($form_state->get('current_Action') as $id) {
        if (!isset($current_plugins['action'][$id])) {
          $current_plugins['action'][$id] = $action_defs[$id];
        }
      }
    }
    if (!empty($form_state->get('current_Condition'))) {
      foreach ($form_state->get('current_Condition') as $id) {
        if (!isset($current_plugins['condition'][$id])) {
          $current_plugins['condition'][$id] = $condition_defs[$id];
        }
      }
    }

    // Build array of active plugin definitions from all sets.
    $all_plugins = $other_plugins;
    foreach ($current_plugins as $plugin_type => $plugindefs) {
      foreach ($current_plugins[$plugin_type] as $id => $def) {
        if (!isset($all_plugins[$plugin_type][$id])) {
          $all_plugins[$plugin_type][$id] = $def;
        }
      }
    }

    // Check available event dependencies.
    foreach ($form['plugins']['Event']['add']['#options'] as $id => $value) {
      // Skip if there are no dependencies.
      if (empty($event_defs[$id]['bcubed_dependencies'])) {
        continue;
      }
      foreach ($event_defs[$id]['bcubed_dependencies'] as $dependency) {
        // First check same set for the required plugin.
        if ($dependency['same_set'] && !isset($current_plugins[$dependency['plugin_type']][$dependency['plugin_id']])) {
          // Remove event.
          unset($form['plugins']['Event']['add']['#options'][$id]);
          break;
        }
        // If event is generated, the generating plugin must be in another conditionset.
        if ($dependency['dependency_type'] == 'generated_by' && !isset($other_plugins[$dependency['plugin_type']][$dependency['plugin_id']])) {
          // Remove event.
          unset($form['plugins']['Event']['add']['#options'][$id]);
          break;
        }
        // finally, if the event simply requires another plugin from any set, check that condition.
        if (!isset($all_plugins[$dependency['plugin_type']][$dependency['plugin_id']])) {
          // Remove event.
          unset($form['plugins']['Event']['add']['#options'][$id]);
          break;
        }
      }
    }

    // Check available condition dependencies.
    foreach ($form['plugins']['Condition']['add']['#options'] as $id => $value) {
      // Skip if there are no dependencies.
      if (empty($condition_defs[$id]['bcubed_dependencies'])) {
        continue;
      }
      foreach ($condition_defs[$id]['bcubed_dependencies'] as $dependency) {
        // Special case for conditions.
        if ($dependency['plugin_id'] == '*' && $dependency['dependency_type'] == 'generated_by') {
          // Check current set for generated events.
          foreach ($current_plugins['event'] as $event) {
            if (!empty($event['bcubed_dependencies'])) {
              foreach ($event['bcubed_dependencies'] as $event_dependency) {
                if ($event_dependency['dependency_type'] == 'generated_by') {
                  continue 3;
                }
              }
            }
          }
          // If the above loops did not result in a continue, no appropriate events were found.
          unset($form['plugins']['Condition']['add']['#options'][$id]);
          break;
        }
        // Check same set for required plugin.
        if ($dependency['same_set'] && !isset($current_plugins[$dependency['plugin_type']][$dependency['plugin_id']])) {
          // Remove condition.
          unset($form['plugins']['Condition']['add']['#options'][$id]);
          break;
        }
        // If the condition simply requires another plugin from any set, check that condition.
        if (!isset($all_plugins[$dependency['plugin_type']][$dependency['plugin_id']])) {
          // Remove condition.
          unset($form['plugins']['Condition']['add']['#options'][$id]);
          break;
        }
      }
    }

    // Check available action dependencies.
    foreach ($form['plugins']['Action']['add']['#options'] as $id => $value) {
      // Skip if there are no dependencies.
      if (empty($action_defs[$id]['bcubed_dependencies'])) {
        continue;
      }
      foreach ($action_defs[$id]['bcubed_dependencies'] as $dependency) {
        // First check same set for the required plugin.
        if ($dependency['same_set'] && !isset($current_plugins[$dependency['plugin_type']][$dependency['plugin_id']])) {
          // Remove action.
          unset($form['plugins']['Action']['add']['#options'][$id]);
          break;
        }
        // If the action simply requires another plugin from any set, check that condition.
        if (!isset($all_plugins[$dependency['plugin_type']][$dependency['plugin_id']])) {
          // Remove action.
          unset($form['plugins']['Action']['add']['#options'][$id]);
          break;
        }
      }
    }

    return TRUE;
  }

  /**
   * AJAX add plugin callback.
   */
  public function ajaxAddPlugin(array &$form, FormStateInterface &$form_state) {
    $plugin_type = $form_state->getTriggeringElement()['#parents'][1];
    $form['plugins'][$plugin_type]['add']['#value'] = 'none';
    return $form['plugins'];
  }

  /**
   * AJAX remove plugin callback (returns section of previously rebuilt form)
   */
  public function ajaxRemovePlugin(array &$form, FormStateInterface &$form_state) {
    $plugin_type = $form_state->getTriggeringElement()['#parents'][1];
    $pluginsettings = $form_state->get($plugin_type . '_settings');
    for ($i = 0, $size = count($pluginsettings); $i < $size; ++$i) {
      if ($pluginsettings[$i]) {
        foreach ($pluginsettings[$i] as $key => $value) {
          $form['plugins'][$plugin_type][$i]['settings'][$key]['#value'] = $value;
        }
      }
    }
    return $form['plugins'];
  }

  /**
   * Remove plugin callback.
   */
  public function removePlugin(array &$form, FormStateInterface &$form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    $plugin_type = $parents[1];
    $id = $parents[2];
    $pluginvalues = $form_state->getUserInput()['plugins'][$plugin_type];
    unset($pluginvalues[$id]);
    unset($pluginvalues['add']);
    $pluginvalues = array_values($pluginvalues);
    $current_plugins = $form_state->get('current_' . $plugin_type);
    unset($current_plugins[$id]);
    $current_plugins = array_values($current_plugins);
    $form_state->set($plugin_type . '_settings', $pluginvalues);
    $form_state->set('current_' . $plugin_type, $current_plugins);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $entity->set('label', $form_state->getValue('label'));
    $entity->set('id', $form_state->getValue('id'));
    $entity->set('description', $form_state->getValue('description'));
    $plugin_types = [
      'actions' => 'Action',
      'events' => 'Event',
      'conditions' => 'Condition',
    ];
    foreach ($plugin_types as $entityproperty => $formproperty) {
      $plugins = [];
      $current_plugins = $form_state->get('current_' . $formproperty, NULL);
      if (!is_null($current_plugins)) {
        $pluginvalues = $form_state->getValue(['plugins', $formproperty]);
        for ($i = 0, $size = count($current_plugins); $i < $size; ++$i) {
          unset($pluginvalues[$i]['remove']);
          unset($pluginvalues[$i]['settings']);
          $plugins[] = [
            'id' => $current_plugins[$i],
            'data' => $pluginvalues[$i],
          ];
        }
      }
      $entity->set($entityproperty, $plugins);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $condition_set = $this->entity;

    // If the condition set is new, set to active.
    if ($condition_set->isNew()) {
      $condition_set->enable();
    }
    $status = $condition_set->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Condition Set.', [
          '%label' => $condition_set->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Condition Set.', [
          '%label' => $condition_set->label(),
        ]));
    }
    $form_state->setRedirect('entity.condition_set.collection');
  }

}
