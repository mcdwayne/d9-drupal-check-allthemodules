<?php

namespace Drupal\flexiform_rules\Plugin\FormEnhancer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flexiform\FormEnhancer\ConfigurableFormEnhancerBase;
use Drupal\flexiform\FormEnhancer\SubmitButtonFormEnhancerTrait;
use Drupal\rules\Engine\ExpressionManagerInterface;
use Drupal\rules\Context\ContextDefinition as RulesContextDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A flexiform enhancer to trigger rules on submission.
 *
 * @FormEnhancer(
 *   label = @Translation("Rules"),
 *   id = "submit_button_rules",
 * )
 */
class SubmitButtonRules extends ConfigurableFormEnhancerBase implements ContainerFactoryPluginInterface {
  use SubmitButtonFormEnhancerTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedEvents = [
    'process_form',
  ];

  /**
   * Rules Storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct a new Submit Button rules plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ExpressionManagerInterface $expression_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->expressionManager = $expression_manager;
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
      $container->get('plugin.manager.rules_expression')
    );
  }

  /**
   * Get the rules storage handler.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage for the rules component.
   */
  protected function rulesStorage() {
    return $this->entityTypeManager->getStorage('rules_component');
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    foreach ($this->locateSubmitButtons() as $path => $label) {
      $original_path = $path;
      $path = str_replace('][', '::', $path);
      $form[$path] = [
        '#type' => 'details',
        '#title' => $this->t('@label Button Submission Rules', ['@label' => $label]),
        '#description' => 'Array Parents: ' . $original_path,
        '#tree' => TRUE,
        '#open' => TRUE,
      ];

      $form[$path]['rules'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Rule'),
          $this->t('Weight'),
          $this->t('Operations'),
        ],
        '#empty' => $this->t('This submit button has no rules configured.'),
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'rule-weight',
          ],
        ],
      ];

      $max_weight = 0;
      if (!empty($this->configuration[$path]['rules'])) {
        foreach ($this->configuration[$path]['rules'] as $rule_name => $info) {
          $rule = $this->rulesStorage()->load($rule_name);
          if (!$rule) {
            continue;
          }

          $form[$path]['rules'][$rule_name] = [
            '#attributes' => [
              'class' => ['draggable'],
            ],
            '#weight' => $info['weight'],
            'rule' => $rule->toLink($rule->label(), 'edit-form')->toRenderable(),
            'weight' => [
              '#type' => 'weight',
              '#title' => $this->t('Execution Order for @title', ['@title' => $rule->label()]),
              '#title_display' => 'invisible',
              '#default_value' => $info['weight'],
              '#attributes' => ['class' => ['rule-weight']],
            ],
            'operations' => [
              '#type' => 'container',
              'remove' => [
                '#type' => 'submit',
                '#value' => $this->t('Remove @title', ['@title' => $rule->label()]),
                '#submit' => [
                  [$this, 'configurationFormSubmitRemoveRule'],
                ],
                '#submit_path' => $path,
                '#rule_name' => $rule->id(),
              ],
            ],
          ];

          $max_weight = ($max_weight > $info['weight']) ? $max_weight : $info['weight'];
        }
      }

      $parents = $form['#parents'];
      $form[$path]['rules']['__new_rule'] = [
        '#attributes' => [
          'class' => ['draggable'],
        ],
        '#weight' => $max_weight + 1,
        'rule' => [
          '#type' => 'container',
          'label' => [
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
          ],
          'id' => [
            '#type' => 'machine_name',
            '#required' => FALSE,
            '#description' => $this->t('A unique machine-readable name. Can only contain lowecase letters numbers and underscores.'),
            '#machine_name' => [
              'exists' => [static::class, 'configurationFormRuleExists'],
              'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
              'source' => array_merge(
                $form['#array_parents'],
                [$path, 'rules', '__new_rule', 'rule', 'label']
              ),
            ],
          ],
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Execution Order for New Rule'),
          '#title_display' => 'invisible',
          '#default_value' => $max_weight,
          '#attributes' => ['class' => ['rule-weight']],
        ],
        'operations' => [
          '#type' => 'container',
          'add' => [
            '#name' => 'add' . $path . 'rule',
            '#type' => 'submit',
            '#value' => $this->t('Add New Rule'),
            '#submit' => [
              [$this, 'configurationFormSubmitAddRule'],
            ],
            '#submit_path' => $path,
            '#limit_validation_errors' => [
              array_merge($form['#parents'], [$path, 'rules', '__new_rule']),
            ],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * Form Submit.
   */
  public function configurationFormSubmit(array $form, FormStateInterface $form_state) {
    $parents = $form['#parents'];
    $values = $form_state->getValue($parents);

    foreach ($values as $path => $rules) {
      $this->configuration[$path]['rules'] = [];
      foreach ($rules['rules'] as $rule_name => $info) {
        if ($rule_name != '__new_rule') {
          $this->configuration[$path]['rules'][$rule_name] = [
            'weight' => $info['weight'],
          ];
        }
      }
    }
  }

  /**
   * Form submit for removing a rule.
   */
  public function configurationFormSubmitRemoveRule($form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    unset($this->configuration[$button['#submit_path']]['rules'][$button['#rule_name']]);
    $form_state->setRebuild();
  }

  /**
   * Form submit for adding a rule.
   */
  public function configurationFormSubmitAddRule($form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $array_parents = $button['#array_parents'];
    array_pop($array_parents); array_pop($array_parents);

    $row = NestedArray::getValue($form, $array_parents);
    $values = $form_state->getValue($row['#parents']);

    $rule_info = $values['rule'];
    $component = $this->rulesStorage()->create([
      'label' => $rule_info['label'],
      'id' => $rule_info['id'],
    ]);
    $component->setExpression($this->expressionManager->createRule());

    $context_definitions = $this->formDisplay
      ->getFormEntityManager()
      ->getContextDefinitions();
    $rules_context_definitions = [];
    foreach ($context_definitions as $namespace => $context_definition) {

      // I don't expext rules will cope very well with an empty $namespace.
      if ($namespace == $this->formDisplay->getBaseEntityNamespace() && $namespace == '') {
        $namespace = 'base_entity';
      }

      $rules_context_definitions[$namespace] = new RulesContextDefinition(
        $context_definition->getDataType(),
        $context_definition->getLabel(),
        $context_definition->isRequired(),
        $context_definition->isMultiple(),
        $context_definition->getDescription(),
        $context_definition->getDefaultValue()
      );
      $rules_context_definitions[$namespace]->setConstraints($context_definition->getConstraints());
    }
    $component->setContextDefinitions($rules_context_definitions);
    $component->save();

    $this->configuration[$button['#submit_path']]['rules'][$component->id()] = [
      'weight' => $values['weight'],
    ];

    // Unset the input.
    $input = &$form_state->getUserInput();
    NestedArray::setValue($input, $row['#parents'], []);

    // Set the form to rebuild.
    $form_state->setRebuild();
  }

  /**
   * Form Check For whether the rule exists.
   *
   * I made this static because something about the serialization wasn't
   * working.
   */
  public static function configurationFormRuleExists($name) {
    return (bool) \Drupal::service('entity_type.manager')->getStorage('rules_component')->load($name);
  }

  /**
   * Process Form Enhancer.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    foreach (array_filter($this->configuration) as $key => $redirect) {
      $array_parents = explode('::', $key);
      $button = NestedArray::getValue($element, $array_parents, $exists);
      if ($exists) {
        if (empty($button['#submit'])) {
          $button['#submit'] = !empty($form['#submit']) ? $form['#submit'] : [];
        }
        $button['#submit'][] = [$this, 'formSubmitRules'];
        $button['#submit_rules'] = $this->configuration[$key]['rules'];
        NestedArray::setValue($element, $array_parents, $button);
      }
    }

    return $element;
  }

  /**
   * Fire rules component on submit.
   */
  public function formSubmitRules($form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    $rules = $button['#submit_rules'];
    uasort($rules, [SortArray::class, 'sortByWeightElement']);

    foreach ($rules as $rule_name => $info) {
      $rule = $this->rulesStorage()->load($rule_name);

      // Prepare arguments for rule execution.
      $arguments = $this->formDisplay->getFormEntityManager()->getFormEntities();
      if ($this->formDisplay->getBaseEntityNamespace() == '' && isset($arguments[''])) {
        $arguments['base_entity'] = $argumets[''];
        unset($arguments['']);
      }

      // Fire the rule.
      $rule->getComponent()->executeWithArguments($arguments);
    }
  }

}
