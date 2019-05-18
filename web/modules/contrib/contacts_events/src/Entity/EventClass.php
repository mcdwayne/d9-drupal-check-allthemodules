<?php

namespace Drupal\contacts_events\Entity;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\rules\Context\ContextConfig;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Context\ContextDefinitionInterface;
use Drupal\rules\Engine\ExecutionState;
use Drupal\rules\Exception\EvaluationException;

/**
 * Defines the Class entity.
 *
 * @ConfigEntityType(
 *   id = "contacts_events_class",
 *   label = @Translation("Class"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\contacts_events\EventClassListBuilder",
 *     "form" = {
 *       "add" = "Drupal\contacts_events\Form\EventClassForm",
 *       "edit" = "Drupal\contacts_events\Form\EventClassForm",
 *       "delete" = "Drupal\contacts_events\Form\EventClassDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "contacts_events_class",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "weight",
 *     "selectable",
 *     "contexts",
 *     "expression",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/event-classes/{contacts_events_class}",
 *     "add-form" = "/admin/structure/event-classes/add",
 *     "edit-form" = "/admin/structure/event-classes/{contacts_events_class}/edit",
 *     "delete-form" = "/admin/structure/event-classes/{contacts_events_class}/delete",
 *     "collection" = "/admin/structure/event-classes"
 *   }
 * )
 */
class EventClass extends ConfigEntityBase implements EventClassInterface {

  /**
   * The unique ID of the event class.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the event class.
   *
   * @var string
   */
  protected $label;

  /**
   * The type this class is for.
   *
   * @var string
   */
  protected $type;

  /**
   * The weight of this class.
   *
   * @var int
   */
  protected $weight;

  /**
   * Whether this class is selectable.
   *
   * @var bool
   */
  protected $selectable;

  /**
   * The contexts configuration.
   *
   * @var array
   */
  protected $contexts = [];

  /**
   * The expression configuration.
   *
   * @var array|null
   */
  protected $expression;

  /**
   * The build expression object.
   *
   * @var \Drupal\rules\Engine\ExpressionInterface
   */
  protected $expressionObject;

  /**
   * Get the expression object.
   *
   * @return \Drupal\rules\Engine\ConditionExpressionContainerInterface
   *   The expression object.
   */
  protected function getExpression() {
    if (!isset($this->expressionObject)) {
      $this->expressionObject = \Drupal::service('plugin.manager.rules_expression')
        ->createInstance($this->expression['id'] ?? 'rules_and', $this->expression ?: []);
    }
    return $this->expressionObject;
  }

  /**
   * Update the expression configuration array.
   */
  protected function updateExpressionConfig() {
    $this->expression = $this->getExpression()->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function addCondition($condition_id, ContextConfig $config = NULL) {
    $this->getExpression()->addCondition($condition_id, $config);
    $this->updateExpressionConfig();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addContext($name, $definition) {
    if ($definition instanceof ContextDefinitionInterface) {
      $definition = $definition->toArray();
    }
    $this->contexts[$name] = $definition;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(array $contexts) {
    // An order item is required.
    if (!isset($contexts['order_item'])) {
      throw new \InvalidArgumentException('Cannot evaluate an event class without an order item.');
    }

    // Check that we are using the right order type.
    if ($this->type != 'global' && $this->type != $contexts['order_item']->bundle()) {
      return FALSE;
    }

    // If our expression is empty, we always match.
    if (empty($this->expression)) {
      return TRUE;
    }

    // Set up the state.
    $state = ExecutionState::create();

    // Add the context based on the type.
    $definition = ContextDefinition::create('entity:commerce_order_item')
      ->addConstraint('bundle', $this->type);
    $state->setVariable('order_item', $definition, $contexts['order_item']);

    // Add any additional context.
    foreach ($contexts as $name => $value) {
      // Skip unknown context.
      if (isset($this->contexts[$name])) {
        $definition = ContextDefinition::createFromArray($this->contexts[$name]);
        $state->setVariable($name, $definition, $value);
      }
    }

    // Attempt execution, treating failures as FALSE.
    try {
      return $this->getExpression()->executeWithState($state);
    }
    catch (EvaluationException $exception) {
      return FALSE;
    }
  }

  /**
   * Find the appropriate class for an order item.
   *
   * @param \Drupal\contacts_events\Entity\EventClassInterface[] $classes
   *   An array of classes.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item to check.
   * @param array $context
   *   An array of additional context.
   *
   * @return \Drupal\contacts_events\Entity\EventClassInterface|null
   *   The matching class or NULL if there is no match.
   */
  public static function findClass(array $classes, OrderItemInterface $order_item, array $context = []) {
    // Ensure our items are sorted.
    uasort($classes, [static::class, 'sort']);

    // Add the order item into the context.
    $context['order_item'] = $order_item;

    // Loop over until we find a match.
    foreach ($classes as $class) {
      if ($class->evaluate($context)) {
        return $class;
      }
    }
  }

}
