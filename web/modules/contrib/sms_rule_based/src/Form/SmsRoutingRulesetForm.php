<?php

namespace Drupal\sms_rule_based\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms_rule_based\Entity\SmsRoutingRuleset;
use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase;

/**
 * Provides the form for configuring rule-based ruleset rules.
 */
class SmsRoutingRulesetForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\sms_rule_based\Entity\SmsRoutingRuleset $ruleset */
    $ruleset = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#description' => t('The name for this routing ruleset'),
      '#required' => TRUE,
      '#attributes' => $ruleset->isNew() ? array() : array('disabled' => 'disabled'),
      '#default_value' => $ruleset->get('label'),
    );

    $form['name'] = array(
      '#type' => 'machine_name',
      '#title' => t('Name'),
      '#description' => t('The name for this routing ruleset'),
      '#required' => TRUE,
      '#default_value' => $ruleset->get('name'),
      '#machine_name' => array(
        'source' => ['label'],
        'exists' => [$this, 'rulesetExists'],
      ),
    );

    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable this routing rule'),
      '#default_value' => $ruleset->get('enabled'),
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#description' => t('Description of what this routing ruleset does.'),
      '#default_value' => $ruleset->get('description'),
    );

    $form['routing'] = array(
      '#type' => 'fieldset',
      '#title' => t('Rule-based routing conditions'),
      '#collapsible' => TRUE,
    );

    $form['routing']['_ALL_TRUE_'] = array(
      '#type' => 'checkbox',
      '#title' => t('All rules must pass'),
      '#default_value' => $ruleset->get('_ALL_TRUE_'),
    );

    // The table of all rule types and their values.
    $form['routing']['rules'] = $this->buildRulesetRulesTable($ruleset->getRules(), 'rules-wrapper');

    $form['selection'] = array(
      '#type' => 'fieldset',
      '#title' => t('Gateway through which SMS will be routed if the conditions match'),
      '#collapsible' => TRUE,
    );

    $gateway = $ruleset->get('gateway');
    $options = array_map(function($value) {
        return $value->label();
      }, SmsGateway::loadMultiple());

    $form['selection']['gateway'] = array(
      '#type' => 'select',
      '#title' => t('Gateway'),
      '#options' => $options,
      '#default_value' => $gateway ?: $this->config('sms.settings')->get('fallback_gateway'),
    );

    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight'),
      '#default_value' => $ruleset->get('weight'),
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    // In editing mode, add the delete button
    if (!$ruleset->isNew()) {
      $form['actions']['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
        '#submit' => [$this, 'delete'],
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Update all the existing rules based on submitted form values.
    /** @var \Drupal\sms_rule_based\Entity\SmsRoutingRuleset $ruleset */
    $ruleset = $this->entity;
    $rules = [];
    $submitted_rules = $form_state->getValue('rules');
    unset($submitted_rules['new']);
    foreach ($submitted_rules as $rule_name => $rule) {
      unset($rule['operation']);
      $rule['name'] = $rule_name;
      $rule['type'] = $ruleset->getRule($rule_name)->getType();
      $rule['operand'] = $ruleset->getRule($rule_name)->processWidgetValue($rule['operand']);
      $rules[$rule_name] = $rule;
    }
    $ruleset->setRules($rules);

    // Check if 'Add rule' button was clicked, and add a new rule entry.
    if ($triggering_element['#value']->getUntranslatedString() === 'Add rule') {
      $ruleset->addRule($this->defaultNewRule($form_state->getValue(['rules', 'new', 'type'])));
    }

    // Delete the specified rule if the 'Delete rule' button was clicked.
    if ($triggering_element['#value']->getUntranslatedString() === 'Delete rule') {
      $rule_name = $triggering_element['#name'];
      $ruleset->removeRule($rule_name);
    }

    // Save the entire ruleset if the save button is clicked.
    if ($triggering_element['#value']->getUntranslatedString() === 'Save') {
      // Make a ruleset from the form submissions.
      /** @var  \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginInterface $rule */
      foreach ($ruleset->getRules() as $rule_name => $rule) {
        if ($rule->isEnabled() && empty($rule->getOperand())) {
          // @todo: Errors not showing on form elements.
          $form_state->setErrorByName("rules[$rule_name][operand]",
            $this->t('No expression assigned to the "@rule" rule.', ['@rule' => $rule->getLabel()]));
        }
      }
      if (!count($ruleset->getRules())) {
        $form_state->setErrorByName('label', $this->t('No rule has been created in this ruleset.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirect('entity.sms_routing_ruleset.list');
  }

  /**
   * {@inheritdoc}
   */
  function delete(array $form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#value'] == $this->t('Delete')) {
      // Redirect to confirm form for delete.
      $form_state->setRedirect('entity.sms_routing_ruleset.delete_form', ['sms_routing_ruleset' => $this->entity->id()]);
    }
  }

  /**
   * Callback for machine_name validation.
   */
  public function rulesetExists($machine_name) {
    return (bool) SmsRoutingRuleset::load($machine_name);
  }

  /**
   * Callback to update form via ajax.
   */
  public function ajaxUpdateRules(array $form, FormStateInterface $form_state) {
    return $form['routing']['rules'];
  }

  /**
   * Provides the different rule type plugins available.
   */
  protected function getRuleTypes() {
    return array_map(function($value) {
      return $value['label'];
    }, \Drupal::service('plugin.manager.sms_routing_rule')->getDefinitions());
  }

  /**
   * Builds the ruleset rules table with a CSS wrapper id
   *
   * @param \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginCollection $rules
   *   The collection of rules to be displayed.
   * @param string $css_wrapper_id
   *   The CSS ID to be used for the wrapper around the table.
   *
   * @return array
   *   A form array.
   */
  protected function buildRulesetRulesTable($rules, $css_wrapper_id) {
    $element = array(
      '#type' => 'table',
      '#header' => array('Enable', 'Type', 'Operator', 'Expression', 'Negate', ''),
      '#empty' => $this->t('No rules have been added yet.'),
      '#prefix' => '<div id="' . $css_wrapper_id . '">',
      '#suffix' => '</div>',
    );
    /** @var \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginInterface $rule */
    foreach ($rules as $rule) {
      $element[$rule->getName()] = $this->buildRulesetRuleRow($rule);
    }
    // Build blank rule form for new rulesets.
    $element['new'] = $this->buildNewRuleRow();
    return $element;
  }

  /**
   * Builds a row in the ruleset rules' table.
   *
   * @param \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginInterface $rule
   *   The rule for which the row is to be built.
   *
   * @return array
   *   A single row element.
   */
  protected function buildRulesetRuleRow($rule) {
    return array(
      'enabled' => array(
        '#type' => 'checkbox',
        '#default_value' => $rule->isEnabled(),
      ),
      'type' => array(
        '#type' => 'inline_template',
        '#template' => '<div title="{{ description }}">{{ label }}</div>',
        '#context' => ['label' => $rule->getLabel(), 'description' => $rule->getDescription()],
      ),
      'operator' => array(
        '#type' => 'select',
        '#options' => SmsRoutingRulePluginBase::getOperatorTypes(),
        '#attributes' => ['title' => SmsRoutingRulePluginBase::getOperatorTypesHelp()],
        '#default_value' => $rule->getOperator(),
      ),
      'operand' => $rule->getWidget() + array(
        '#type' => 'textfield',
        '#title' => $this->t('Rule operand'),
        '#title_display' => 'invisible',
        '#default_value' => $rule->getOperand(),
      ),
      'negated' => array(
        '#type' => 'checkbox',
        '#title' => $this->t('Negate'),
        '#default_value' => $rule->isNegated(),
      ),
      'operation' => array(
        '#type' => 'button',
        '#value' => $this->t('Delete rule'),
        '#id' => Html::cleanCssIdentifier($rule->getName()),
        '#name' => $rule->getName(),
        '#ajax' => [
          'callback' => [$this, 'ajaxUpdateRules'],
          'wrapper' => 'rules-wrapper',
        ],
        '#limit_validation_errors' => [],
      ),
    );
  }

  /**
   * Builds the table row for adding new rulesets.
   *
   * @return array
   *   A row element with add new button and other markup.
   */
  protected function buildNewRuleRow() {
    return [
      'enabled' => ['#markup' => ''],
      'type' => [
        '#type' => 'select',
        '#title' => $this->t('Rule type'),
        '#title_display' => 'invisible',
        '#options' => $this->getRuleTypes(),
      ],
      'operator' => ['#markup' => ''],
      'operand' => ['#markup' => $this->t('Select the <b>rule type</b> from the left and click on <em>"Add rule"</em> to add a new rule.')],
      'negated' => ['#markup' => ''],
      'operation' => [
        '#type' => 'button',
        '#value' => $this->t('Add rule'),
        '#id' => 'add-button',
        '#ajax' => [
          'callback' => [$this, 'ajaxUpdateRules'],
          'wrapper' => 'rules-wrapper',
        ],
        '#limit_validation_errors' => [],
      ],
    ];
  }

  /**
   * Creates a new default SMS routing rule given the rule type.
   *
   * @param string $rule_type
   *   The routing rule type.
   *
   * @return array
   *   A new rule containing default values.
   */
  protected function defaultNewRule($rule_type) {
    $random = new Random();
    return [
      'name' => $rule_type . '_' . $random->name(8, TRUE),
      'type' => $rule_type,
      'operator' => SmsRoutingRulePluginBase::EQ,
      'enabled' => TRUE,
      'operand' => '',
      'negated' => FALSE,
    ];
  }

}
