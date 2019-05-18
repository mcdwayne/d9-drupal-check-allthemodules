<?php

namespace Drupal\business_rules\Entity;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;

/**
 * Defines the Action entity.
 *
 * @ConfigEntityType(
 *   id = "business_rules_action",
 *   label = @Translation("Business Rules Action"),
 *   handlers = {
 *     "list_builder" = "Drupal\business_rules\ActionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\business_rules\Form\ActionForm",
 *       "edit" = "Drupal\business_rules\Form\ActionForm",
 *       "delete" = "Drupal\business_rules\Form\ActionDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\business_rules\ActionHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "action",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" =
 *   "/admin/config/workflow/business_rules/action/{business_rules_action}",
 *     "add-form" = "/admin/config/workflow/business_rules/action/add",
 *     "edit-form" = "/admin/config/workflow/business_rules/action/{business_rules_action}/edit",
 *     "delete-form" = "/admin/config/workflow/business_rules/action/{business_rules_action}/delete",
 *     "collection" = "/admin/config/workflow/business_rules/action/collection/{view_mode}"
 *   }
 * )
 */
class Action extends BusinessRulesItemBase implements ActionInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type = 'business_rules_action') {
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemManager() {
    return \Drupal::getContainer()->get('plugin.manager.business_rules.action');
  }

  /**
   * {@inheritdoc}
   */
  public function getBusinessRuleItemType() {
    return 'action';
  }

  /**
   * {@inheritdoc}
   */
  public function getBusinessRuleItemTranslatedType() {
    return t('Action');
  }

  /**
   * {@inheritdoc}
   */
  public function save() {

    // Prevent action to have the same name as one existent condition.
    $condition = Condition::load($this->id());
    if (!empty($condition)) {
      $this->id = 'a_' . $this->id();
    }

    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(BusinessRulesEvent $event) {
    $action_type = $this->itemManager->getDefinition($this->getType());
    $reflection = new \ReflectionClass($action_type['class']);
    /** @var \Drupal\business_rules\Plugin\BusinessRulesActionPlugin $defined_action */
    $defined_action = $reflection->newInstance($action_type, $action_type['id'], $action_type);
    $action         = Action::load($this->id());
    $defined_action->processTokens($action, $event);

    return $defined_action->execute($action, $event);
  }

}
