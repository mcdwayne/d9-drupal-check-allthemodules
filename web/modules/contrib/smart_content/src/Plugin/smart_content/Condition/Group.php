<?php

namespace Drupal\smart_content\Plugin\smart_content\Condition;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\smart_content\Condition\ConditionBase;
use Drupal\smart_content\Condition\ConditionConfigurableBase;
use Drupal\smart_content\Condition\ConditionInterface;
use Drupal\smart_content\ConditionType\ConditionTypeBase;
use Drupal\smart_content\Entity\SmartVariationSet;
use Drupal\smart_content\Form\SmartVariationSetForm;

/**
 * Provides a 'condition_group' ConditionType.
 *
 * @SmartCondition(
 *   id = "group",
 *   label = @Translation("Group"),
 *   group = "common",
 *   weight = 0,
 *   unique = true,
 * )
 */
class Group extends ConditionConfigurableBase {

  //@todo: determine best way to unset extra settings

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $wrapper_id = Html::getUniqueId('condition-group-wrapper');
    $wrapper_items_id = Html::getUniqueId('condition-group-items-wrapper');

    $values = $this->getConfiguration();

    $form = ConditionBase::attachNegateElement($form, $values);

    $form['#attributes']['class'][] = 'condition-group';

    $form['label'] = [
      '#type' => 'container',
      '#markup' => 'Condition group',
      '#attributes' => ['class' => ['condition-label']],
    ];

    $form['op'] = [
      '#title' => 'Operator',
      '#type' => 'select',
      '#options' => [
        'AND' => 'AND',
        'OR' => 'OR',
      ],
      '#default_value' => isset($values['op']) ? $values['op'] : 'AND',
      '#attributes' => [
        'class' => [
          'condition-op',
          'condition-group-operator',
        ],
      ],
    ];

    $form['conditions_config'] = [
      '#type' => 'container',
      '#title' => 'Conditions',
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '-conditions' . '" class="conditions-container group-conditions-container">',
      '#suffix' => '</div>',
    ];

    $form['conditions_config']['condition_items'] = [
      '#type' => 'table',
      '#header' => [t('Condition(s)'), t('Weight'), ''],
      '#prefix' => '<div id="' . $wrapper_items_id . '-conditions' . '" class="conditions-container-items group-conditions-container-items">',
      '#suffix' => '</div>',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $wrapper_items_id . '-order-weight',
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
          '#attributes' => ['class' => [$wrapper_items_id . '-order-weight']],
        ];

        $form['conditions_config']['condition_items'][$condition_id]['remove_condition'] = [
          '#type' => 'submit',
          '#value' => t('Remove Condition'),
          '#submit' => [[$this, 'removeElementCondition']],
          '#attributes' => ['class' => ['align-right', 'remove-condition', 'remove-button']],
          '#limit_validation_errors' => [],
          '#ajax' => [
            'callback' => [$this, 'removeElementConditionAjax'],
            'wrapper' => $wrapper_id . '-conditions',
          ],
        ];
      }
    }

    $form['conditions_config']['add_condition'] = [
      '#type' => 'container',
      '#title' => 'Add Condition',
      '#attributes' => ['class' => ['condition-add-container']],
    ];

    $form['conditions_config']['add_condition']['condition_type'] = [
      '#title' => 'Condition Type',
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => \Drupal::service('plugin.manager.smart_content.condition')
        ->getFormOptions(),
      '#empty_value' => '',
    ];

    $form['conditions_config']['add_condition']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Add Condition'),
      '#validate' => [[$this, 'addElementConditionValidate']],
      '#submit' => [[$this, 'addElementCondition']],
      '#ajax' => [
        'callback' => [$this, 'addElementConditionAjax'],
        'wrapper' => $wrapper_id . '-conditions',
      ],
    ];

    $form['#process'][] = [$this, 'buildWidget'];

    return $form;
  }

  /**
   * Render API callback: builds the formatter settings elements.
   */
  public function buildWidget(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $unique_id = Html::getClass(implode('-', $element['#parents']));

    foreach ($this->getConditions() as $condition_id => $condition) {
      if ($condition instanceof PluginFormInterface) {
        $element['conditions_config']['condition_items'][$condition_id]['remove_condition']['#name'] = 'remove_condition_' . $unique_id . '__' . $condition_id;
      }
    }
    $element['conditions_config']['add_condition']['submit'] += [
      '#name' => 'add_condition_' . $unique_id,
      '#limit_validation_errors' => [
        array_merge($element['#parents'], [
          'conditions_config',
          'add_condition',
          'condition_type',
        ]),
      ],
    ];
    return $element;
  }

  function defaultFieldConfiguration() {
    return [];
  }

  public function evaluate($values, $context) {
    return (bool) $context;
  }

  function getLibraries() {
    $libraries = ['smart_content/condition.common'];
    foreach ($this->getConditions() as $condition) {
      $libraries = array_merge($libraries, $condition->getLibraries());
    }
    return $libraries;
  }

  public function getAttachedSettings($processed_client = FALSE) {
    $definition = $this->getPluginDefinition();
    $condition_settings = [];
    foreach ($this->getConditions() as $condition) {
      $condition_settings[] = $condition->getAttachedSettings();
    }
    return [
      'field' => [
        'pluginId' => $this->getPluginId(),
        'unique' => $definition['unique'],
        'conditions' => $condition_settings,
      ],
      'settings' => [
        'op' => $this->getConfiguration()['op'],
        'negate' => $this->getConfiguration()['negate'],
      ],
    ];
  }

  /**
   * @param \Drupal\smart_content\Condition\ConditionInterface $condition
   */
  public function addCondition(ConditionInterface $condition) {
    if ($condition->id() === NULL) {
      $id = SmartVariationSet::generateUniquePluginId($condition, array_keys($this->getConditions()));
      $condition->setId($id);
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
        $plugins[] = \Drupal::service('plugin.manager.smart_content.condition')
          ->createInstance($value['plugin_id'], $value);
      }
    }
    return $plugins;
  }

  // @todo: determine if there is a better way to do this. copy better way to block form.
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
    $array_parents = array_slice($button['#array_parents'], 0, -1);
    $parents = array_slice($button['#parents'], 0, -1);
    $parents[] = 'condition_type';
    if (!$value = NestedArray::getValue($form_state->getUserInput(), $parents)) {
      $form_state->setError(NestedArray::getValue($form, $array_parents), 'Condition type required.');
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

    //@todo: Fix nested
    $this->addCondition(\Drupal::service('plugin.manager.smart_content.condition')
      ->createInstance($type));
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

    //@todo: add nested remove functionality.
    $this->removeCondition($name);
    $form_state->setRebuild();

  }

  public function removeElementConditionAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
  }

  public function sortConditions() {
    if ($this->getConditions()) {
      uasort($this->conditions, function ($first, $second) {
        return $first->getWeight() > $second->getWeight();
      });
    }
  }

  /**
   * @inheritdoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    self::attachTableConditionWeight($form_state->getValues()['conditions_config']['condition_items']);
    $configuration = $form_state->getValues();
    unset($configuration['conditions_config']);
    foreach ($this->getConditions() as $condition_id => $condition) {
      SmartVariationSetForm::pluginFormSubmit($condition, $form, $form_state, [
        'conditions_config',
        'condition_items',
        $condition_id,
        'plugin_form',
      ]);
    }
    $this->setConfiguration($configuration);
  }

  /**
   * @inheritdoc
   */
  public function writeChangesToConfiguration() {
    $configuration = $this->getConfiguration();
    $configuration['conditions_settings'] = [];
    foreach ($this->getConditions() as $condition_id => $condition) {
      $condition->writeChangesToConfiguration();
      $configuration['conditions_settings'][] = $condition->getConfiguration();
    }
    $this->setConfiguration($configuration);
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }

}
