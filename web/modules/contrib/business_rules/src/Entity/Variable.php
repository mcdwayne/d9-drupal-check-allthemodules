<?php

namespace Drupal\business_rules\Entity;

use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\VariableInterface;

/**
 * Defines the Variable entity.
 *
 * @ConfigEntityType(
 *   id = "business_rules_variable",
 *   label = @Translation("Business Rules Variable"),
 *   handlers = {
 *     "list_builder" = "Drupal\business_rules\VariableListBuilder",
 *     "form" = {
 *       "add" = "Drupal\business_rules\Form\VariableForm",
 *       "edit" = "Drupal\business_rules\Form\VariableForm",
 *       "delete" = "Drupal\business_rules\Form\VariableDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\business_rules\VariableHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "variable",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/workflow/business_rules/variable/{business_rules_variable}",
 *     "add-form" = "/admin/config/workflow/business_rules/variable/add",
 *     "edit-form" = "/admin/config/workflow/business_rules/variable/{business_rules_variable}/edit",
 *     "delete-form" = "/admin/config/workflow/business_rules/variable/{business_rules_variable}/delete",
 *     "collection" = "/admin/config/workflow/business_rules/variable/collection/{view_mode}"
 *   }
 * )
 */
class Variable extends BusinessRulesItemBase implements VariableInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type = 'business_rules_variable') {
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemManager() {
    return \Drupal::getContainer()
      ->get('plugin.manager.business_rules.variable');
  }

  /**
   * {@inheritdoc}
   */
  public function getBusinessRuleItemType() {
    return 'variable';
  }

  /**
   * {@inheritdoc}
   */
  public function getBusinessRuleItemTranslatedType() {
    return t('Variable');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(BusinessRulesEvent $event) {
    $variable_type = $this->itemManager->getDefinition($this->getType());
    $reflection    = new \ReflectionClass($variable_type['class']);
    /** @var \Drupal\business_rules\Plugin\BusinessRulesVariablePluginInterface $defined_variable */
    $defined_variable = $reflection->newInstance($variable_type, $variable_type['id'], $variable_type);
    $variable         = Variable::load($this->id());
    $defined_variable->processTokens($variable, $event);

    return $defined_variable->evaluate($variable, $event);
  }

}
