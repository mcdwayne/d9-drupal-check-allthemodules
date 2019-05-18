<?php

namespace Drupal\business_rules\Entity;

use Drupal\business_rules\BusinessRuleInterface;
use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Rule entity.
 *
 * @ConfigEntityType(
 *   id = "business_rule",
 *   label = @Translation("Business rules"),
 *   handlers = {
 *     "list_builder" = "Drupal\business_rules\BusinessRuleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\business_rules\Form\BusinessRuleForm",
 *       "edit" = "Drupal\business_rules\Form\BusinessRuleForm",
 *       "delete" = "Drupal\business_rules\Form\BusinessRuleDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\business_rules\BusinessRuleHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "business_rule",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "enabled",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/workflow/business_rules/{business_rule}",
 *     "add-form" = "/admin/config/workflow/business_rules/add",
 *     "edit-form" = "/admin/config/workflow/business_rules/{business_rule}/edit",
 *     "delete-form" = "/admin/config/workflow/business_rules/{business_rule}/delete",
 *     "variables-form" = "/admin/config/workflow/business_rules/{business_rule}/variables",
 *     "collection" = "/admin/config/workflow/business_rules/collection/{view_mode}",
 *     "enable" = "/admin/config/workflow/business_rules/{business_rule}/enable",
 *     "disable" = "/admin/config/workflow/business_rules/{business_rule}/disable",
 *   }
 * )
 */
class BusinessRule extends ConfigEntityBase implements BusinessRuleInterface {

  /**
   * The reactsOnManger.
   *
   * @var \Drupal\business_rules\Plugin\BusinessRulesReactsOnManager
   */
  protected static $reactsOnManager;

  /**
   * The ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The rule description.
   *
   * @var string
   */
  protected $description;

  /**
   * The Rule ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The business rule's items.
   *
   * @var array
   */
  protected $items = [];

  /**
   * The Rule label.
   *
   * @var string
   */
  protected $label;

  /**
   * The trigger that will start the rule.
   *
   * @var string
   */
  protected $reacts_on;

  /**
   * The BusinessRule is enabled or not.
   *
   * @var bool
   */
  protected $status;

  /**
   * The tags to mark this entity.
   *
   * @var array
   */
  protected $tags = [];

  /**
   * The target entity bundle id which this rule is applicable.
   *
   * @var string
   */
  protected $target_bundle;

  /**
   * The entity type id which this rule is applicable.
   *
   * @var string
   */
  protected $target_entity_type;

  /**
   * The Business Rules Util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  protected $util;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type = 'business_rule') {
    parent::__construct($values, $entity_type);

    $this->util            = \Drupal::getContainer()
      ->get('business_rules.util');
    $this->configFactory   = \Drupal::getContainer()->get('config.factory');
    self::$reactsOnManager = \Drupal::getContainer()
      ->get('plugin.manager.business_rules.reacts_on');
  }

  /**
   * {@inheritdoc}
   */
  public function getActions() {
    return is_array($this->actions) ? $this->actions : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    return is_array($this->conditions) ? $this->conditions : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getVariables() {
    return is_array($this->variables) ? $this->variables : [];
  }

  /**
   * {@inheritdoc}
   */
  public function save() {

    // Only save items on the same context as the Business Rule.
    $context_items = [];
    if (count($this->items)) {
      foreach ($this->items as $key => $item) {
        if (!$item instanceof BusinessRulesItemObject) {
          $item = new BusinessRulesItemObject($item['id'], $item['type'], $item['weight']);
        }
        if ($this->checkItemContext($item)) {
          $context_items[$key] = $item->toArray()[$key];
        }
      }
    }

    $this->items = $context_items;

    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function checkItemContext(BusinessRulesItemObject $itemObject) {

    if ($itemObject->getType() == 'condition') {
      $item = Condition::load($itemObject->getId());
    }
    elseif ($itemObject->getType() == 'action') {
      $item = Action::load($itemObject->getId());
    }

    if (empty($item)) {
      return FALSE;
    }

    $entity_type = $this->getTargetEntityType();
    $bundle      = $this->getTargetBundle();
    // Evaluate Target Entity Type.
    if ((($item->getTargetEntityType() == $entity_type || empty($item->getTargetEntityType())) &&
        // Evaluate target bundle.
        ($item->getTargetBundle() == $bundle || empty($item->getTargetBundle())) &&
        // Evaluate ReactsOn events.
        (in_array($this->getReactsOn(), $item->getReactOnEvents()) || count($item->getReactOnEvents()) === 0)) ||
      // Item is not context dependent.
      (!$item->isContextDependent())
    ) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType() {
    return $this->target_entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return $this->target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getReactsOn() {
    return $this->reacts_on;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemMaxWeight() {
    $items = $this->getItems();
    $max   = -10;
    if (is_array($items)) {
      foreach ($items as $item) {
        if ($max < $item->getWeight()) {
          $max = $item->getWeight();
        }
      }
    }

    return $max;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    $obj_items = BusinessRulesItemObject::itemsArrayToItemsObject($this->items);

    return $obj_items;
  }

  /**
   * {@inheritdoc}
   */
  public function getItem($item_id) {
    if (isset($this->items[$item_id])) {
      $item    = $this->items[$item_id];
      $itemObj = new BusinessRulesItemObject($item['id'], $item['type'], $item['weight']);

      return $itemObj;
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function loadAllTags() {
    $business_rules = self::loadMultiple();
    $tags           = [];
    /** @var \Drupal\business_rules\Entity\BusinessRule $business_rule */
    foreach ($business_rules as $business_rule) {
      if (count($business_rule->getTags())) {
        foreach ($business_rule->getTags() as $key => $value) {
          if ($key != '' || $value != '') {
            $tags[$key] = $value;
          }
        }
      }
    }
    ksort($tags);

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getTags() {
    return $this->tags;
  }

  /**
   * {@inheritdoc}
   */
  public function setTags(array $tags) {
    $formatted_tags = [];
    foreach ($tags as $tag) {
      if ($tag != '') {
        $this->util->toSafeLowerString($tag);
        $formatted_tags[$tag] = $tag;
      }
    }
    ksort($formatted_tags);
    $this->tags = $formatted_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(BusinessRulesItemObject $item) {
    $item_array                  = $item->toArray();
    $this->items[$item->getId()] = $item_array[$item->getId()];
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem(BusinessRulesItemObject $item) {
    unset($this->items[$item->getId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundleLabel() {
    $bundles = $this->util->getBundles($this->getTargetEntityType());
    foreach ($bundles as $key => $value) {
      if ($key == $this->getTargetBundle()) {
        if ($key === '') {
          return t('All');
        }

        return $value;
      }
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled($status) {
    $this->status = $status;
  }

  /**
   * {@inheritdoc}
   */
  public function getReactsOnLabel() {
    $reacts = self::getEventTypes();

    foreach ($reacts as $react) {
      foreach ($react as $key => $value) {
        if ($key == $this->getReactsOn()) {
          return $value;
        }
      }
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public static function getEventTypes() {
    $types = [];
    $events = self::$reactsOnManager->getDefinitions();

    uasort($events, function ($a, $b) {
      return ($a['label']->render() > $b['label']->render()) ? 1 : -1;
    });

    foreach ($events as $event) {
      if (isset($types[$event['group']->render()])) {
        $types[$event['group']->render()] += [$event['id'] => $event['label']];
      }
      else {
        $types[$event['group']->render()] = [$event['id'] => $event['label']];
      }

    }

    ksort($types);

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeLabel() {
    $entities = $this->util->getEntityTypes();
    foreach ($entities as $key => $value) {
      if ($key == $this->getTargetEntityType()) {
        return $value;
      }
    }

    return '';
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
      if ((($value->getTargetEntityType() == $entity_type || empty($value->getTargetEntityType())) &&
          // Evaluate target bundle.
          ($value->getTargetBundle() == $bundle || empty($value->getTargetBundle())) &&
          // Evaluate ReactsOn events.
          (in_array($this->getReactsOn(), $value->getReactOnEvents()) || count($value->getReactOnEvents()) === 0)) ||
        // Item is context dependent.
        (!$value->isContextDependent())
      ) {
        $available_items[$key] = $value;
      }
    }

    return $available_items;

  }

}
