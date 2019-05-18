<?php

namespace Drupal\business_rules\Entity;

use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;

/**
 * Defines the Condition entity.
 *
 * @ConfigEntityType(
 *   id = "business_rules_condition",
 *   label = @Translation("Business Rules Condition"),
 *   handlers = {
 *     "list_builder" = "Drupal\business_rules\ConditionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\business_rules\Form\ConditionForm",
 *       "edit" = "Drupal\business_rules\Form\ConditionForm",
 *       "delete" = "Drupal\business_rules\Form\ConditionDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\business_rules\ConditionHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "condition",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/workflow/business_rules/condition/{business_rules_condition}",
 *     "add-form" = "/admin/config/workflow/business_rules/condition/add",
 *     "edit-form" = "/admin/config/workflow/business_rules/condition/{business_rules_condition}/edit",
 *     "delete-form" = "/admin/config/workflow/business_rules/condition/{business_rules_condition}/delete",
 *     "collection" = "/admin/config/workflow/business_rules/condition/collection/{view_mode}"
 *   }
 * )
 */
class Condition extends BusinessRulesItemBase implements ConditionInterface {

  /**
   * Items to be executed if condition fails.
   *
   * @var array
   */
  protected $fail_items = [];

  /**
   * If it's a reverse condition (NOT).
   *
   * @var bool
   */
  protected $reverse;

  /**
   * Items to be executed if condition succeed.
   *
   * @var array
   */
  protected $success_items = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type = 'business_rules_condition') {
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemManager() {
    return \Drupal::getContainer()
      ->get('plugin.manager.business_rules.condition');
  }

  /**
   * {@inheritdoc}
   */
  public function getBusinessRuleItemType() {
    return 'condition';
  }

  /**
   * {@inheritdoc}
   */
  public function getBusinessRuleItemTranslatedType() {
    return t('Condition');
  }

  /**
   * {@inheritdoc}
   */
  public function save() {

    // Prevent condition to have the same name as one existent action.
    $action = Action::load($this->id());
    if (!empty($action)) {
      $this->id = 'c_' . $this->id();
    }

    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function isReverse() {
    return (bool) $this->reverse;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuccessItems() {
    $success_items = BusinessRulesItemObject::itemsArrayToItemsObject($this->success_items);

    return $success_items;
  }

  /**
   * {@inheritdoc}
   */
  public function getFailItems() {
    $fail_items = BusinessRulesItemObject::itemsArrayToItemsObject($this->fail_items);

    return $fail_items;
  }

  /**
   * {@inheritdoc}
   */
  public function removeSuccessItem(BusinessRulesItemObject $item) {
    unset($this->success_items[$item->getId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function removeFailItem(BusinessRulesItemObject $item) {
    unset($this->fail_items[$item->getId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function filterContextAvailableItems(array $items) {

    /** @var \Drupal\business_rules\ItemInterface $value */
    $entity_type     = $this->getTargetEntityType();
    $bundle          = $this->getTargetBundle();
    $available_items = [];

    foreach ($items as $key => $value) {
      // Evaluate Target Entity Type.
      if (((($value->getTargetEntityType() == $entity_type || empty($value->getTargetEntityType())) &&
            // Evaluate target bundle.
            ($value->getTargetBundle() == $bundle || empty($value->getTargetBundle()))) ||
          // Item is not context dependent.
          (!$value->isContextDependent())
        ) &&
        // Not allow one condition to be child of itself.
        ($this->id() != $value->id())
      ) {
        $available_items[$key] = $value;
      }
    }

    return $available_items;

  }

  /**
   * {@inheritdoc}
   */
  public function getMaxItemWeight($success = TRUE) {
    if ($success) {
      $items = $this->success_items;
    }
    else {
      $items = $this->fail_items;
    }
    $max = -10;
    if (is_array($items)) {
      foreach ($items as $item) {
        if ($max < $item['weight']) {
          $max = $item['weight'];
        }
      }
    }

    return $max;
  }

  /**
   * {@inheritdoc}
   */
  public function addSuccessItem(BusinessRulesItemObject $item) {
    $item_array                          = $item->toArray();
    $this->success_items[$item->getId()] = $item_array[$item->getId()];
  }

  /**
   * {@inheritdoc}
   */
  public function addFailItem(BusinessRulesItemObject $item) {
    $item_array                       = $item->toArray();
    $this->fail_items[$item->getId()] = $item_array[$item->getId()];
  }

  /**
   * {@inheritdoc}
   */
  public function process(BusinessRulesEvent $event) {
    $condition_type = $this->itemManager->getDefinition($this->getType());
    $reflection     = new \ReflectionClass($condition_type['class']);
    /** @var \Drupal\business_rules\Plugin\BusinessRulesConditionPlugin $defined_condition */
    $defined_condition = $reflection->newInstance($condition_type, $condition_type['id'], $condition_type);
    $condition         = Condition::load($this->id());
    $defined_condition->processTokens($condition, $event);

    return $defined_condition->process($condition, $event);
  }

}
