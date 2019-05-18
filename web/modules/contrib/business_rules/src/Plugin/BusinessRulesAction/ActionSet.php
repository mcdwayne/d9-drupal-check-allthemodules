<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ActionSet.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "action_set",
 *   label = @Translation("Action set"),
 *   group = @Translation("Action"),
 *   description = @Translation("Set of actions. Only actions with same target Entity and Bundles can be included on the set."),
 *   reactsOnIds = {},
 *   isContextDependent = FALSE,
 * )
 */
class ActionSet extends BusinessRulesActionPlugin {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * We don't want to use the same wait two times for an item.
   *
   * @var array
   */
  private $usedWeight = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration = [], $plugin_id = 'action_set', $plugin_definition = []) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $this->util->container->get('entity_type.manager');
  }

  /**
   * Return all others actions with the same target Entity and Bundle.
   *
   * @param \Drupal\business_rules\Entity\Action $action
   *   The business rules Action.
   *
   * @return array
   *   Array of actions matched.
   */
  public static function getAvailableItems(Action $action) {
    $actions         = Action::loadMultiple();
    $current_items   = $action->getSettings('items');
    $actions_matched = [];
    if (count($actions)) {
      foreach ($actions as $a) {
        if ($action->id() != $a->id() && self::checkInnerAction($action, $a) && !in_array($a->id(), array_keys($current_items))) {
          $actions_matched[] = $a;
        }
      }
    }

    return $actions_matched;
  }

  /**
   * Do not show actionSets that already contains the main_action.
   *
   * Important to avoid infinite action check loops.
   *
   * @param \Drupal\business_rules\ActionInterface $main_action
   *   The main action.
   * @param \Drupal\business_rules\ActionInterface $child_action
   *   The child action.
   *
   * @return bool
   *   I check succeed or fails.
   */
  private static function checkInnerAction(ActionInterface $main_action, ActionInterface $child_action) {
    $check = TRUE;
    if ($child_action->getType() == 'action_set') {
      $actions = $child_action->getSettings();
      foreach ($actions as $action) {
        $action = Action::load($action['action']);
        if ($main_action->id() == $action->id()) {
          $check = FALSE;
          break;
        }
        elseif ($action->getType() == 'action_set') {
          $inner_actions = $action->getSettings();
          foreach ($inner_actions as $inner_action) {
            $inner_action = Action::load($inner_action['action']);
            $check        = self::checkInnerAction($action, $inner_action);
          }
        }
      }
    }

    return $check;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    if ($item->isNew()) {
      $form_state->set('business_rule_action', $item);

      return [];
    }

    // The actions to be executed.
    $settings['items'] = [
      '#type'  => 'details',
      '#title' => t('Actions to execute'),
      '#open'  => TRUE,
    ];

    $settings['items'][] = $this->formItems($form, $form_state, $item);

    return $settings;
  }

  /**
   * Provide the form fields for add Business Rule's Items.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\business_rules\ItemInterface $action
   *   The current action.
   *
   * @return array
   *   The render array.
   */
  public function formItems(array $form, FormStateInterface $form_state, ItemInterface $action) {
    $user_input = $form_state->getUserInput();

    $label     = t('Item');
    $raw_items = $action->getSettings('items');

    $items = [];
    if (is_array($raw_items)) {
      foreach ($raw_items as $key => $item) {
        $itemObj     = new BusinessRulesItemObject($key, $item['type'], $item['weight']);
        $items[$key] = $itemObj;
      }

      uasort($items, function ($a, $b) {
        return $a->getWeight() < $b->getWeight() ? -1 : 1;
      });
    }

    $header = [
      'item_type'   => t('Type'),
      'label'       => $label,
      'weight'      => t('Weight'),
      'id'          => t('Machine name'),
      'subtype'     => t('Subtype'),
      'description' => t('Description'),
      'operations'  => t('Operations'),
      'type'        => [
        'data'  => '',
        'width' => '0px',
      ],
    ];

    $table['items'] = [
      '#type'       => 'table',
      '#header'     => $header,
      '#attributes' => ['id' => 'business_rules-items'],
      '#empty'      => t('There are currently no actions in this action set. Add one by selecting an option below.'),
      '#tabledrag'  => [
        [
          'action'       => 'order',
          'relationship' => 'sibling',
          'group'        => 'items-order-weight',
        ],
      ],
    ];

    if (is_array($items)) {
      foreach ($items as $value) {

        $item = Action::load($value->getId());

        $item_weight       = !empty($item) ? $value->getWeight() : '';
        $route_remove_item = 'business_rules.action_set.items.remove';

        if (!empty($item)) {

          $key = $item->id();

          $listBuilder = $this->entityTypeManager->getListBuilder($item->getEntityTypeId());
          $operations = $listBuilder->buildOperations($item);

          $operations['#links']['remove'] = [
            'title'  => t('Remove'),
            'url'    => Url::fromRoute($route_remove_item, [
              'action_id' => $action->id(),
              'item_id'   => $item->id(),
              'method'    => 'nojs',
            ],
              [
                'attributes' => [
                  'class' => ['use-ajax'],
                ],
              ]
            ),
            'weight' => 1,
          ];
          uasort($operations['#links'], function ($a, $b) {
            return $a['weight'] < $b['weight'] ? -1 : 1;
          });
          foreach ($operations['#links'] as $i => $link) {
            $uri = $this->util->getCurrentUri()->toString();
            $operations['#links'][$i]['url']->setRouteParameter('destination', $uri);
          }

          $item_weight = $this->generateItemWeight('item', $item_weight);

          $weight = [
            '#type'          => 'weight',
            '#title'         => t('Weight for item'),
            '#title_display' => 'invisible',
            '#delta'         => 100,
            '#default_value' => $item_weight,
            '#attributes'    => [
              'class' => ['items-order-weight'],
            ],
          ];

          $table['items'][$key] = [
            '#attributes' => [
              'class' => 'draggable',
              'id'    => $item->id(),
            ],
            '#weight'     => isset($user_input['effects']) ? $user_input['effects'][$key]['weight'] : NULL,
            'item_type'   => ['#markup' => $item->getBusinessRuleItemTranslatedType()],
            'item'        => [
              '#tree' => FALSE,
              'label' => ['#markup' => $item->label()],
            ],
            'weight'      => $weight,
            'id'          => ['#markup' => $item->id()],
            'subtype'     => ['#markup' => $item->getTypeLabel()],
            'description' => ['#markup' => $item->getDescription()],
            'operations'  => $operations,
            'type'        => [
              '#type'  => 'value',
              '#value' => $value->getType(),
            ],
          ];

        }
      }
    }

    $add_action = Link::createFromRoute(t('Add Action'), 'business_rules.action_set.items.table', [
      'action_id' => $action->id(),
      'method'    => 'nojs',
    ], ['attributes' => ['class' => ['use-ajax']]]);

    $table['add'][] = [
      'data' => [
        'add' => [
          '#type'   => 'markup',
          '#markup' => $add_action->toString(),
          '#prefix' => '<div id="business_rule-add_buttons">',
          '#suffix' => '</div>',
        ],
      ],
    ];

    return $table;
  }

  /**
   * Generate the item weight.
   *
   * @param string $settings_type
   *   The settings type: success|fail.
   * @param int $weight
   *   The weight.
   *
   * @return int
   *   The generated weight
   */
  private function generateItemWeight($settings_type, $weight) {

    if (!isset($this->usedWeight[$settings_type])) {
      $this->usedWeight[$settings_type][] = $weight;

      return $weight;
    }

    if (!in_array($weight, $this->usedWeight[$settings_type])) {
      $this->usedWeight[$settings_type][] = $weight;

      return $weight;
    }
    else {
      $weight++;

      return $this->generateItemWeight($settings_type, $weight);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->get('business_rule_action');
    if (!empty($action) && $action->isNew()) {
      $form['actions']['submit']['#value'] = t('Continue');
    }
    // We don't need variables for this action.
    unset($form['variables']);
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings, ItemInterface $item) {
    if (empty($settings['items'])) {
      $settings['items'] = [];
    }
    else {
      foreach ($settings['items'] as $key => $item) {
        $settings['items'][$key]['id'] = $key;
      }
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    $actions = $action->getSettings('items');
    $actions = BusinessRulesItemObject::itemsArrayToItemsObject($actions);

    // Process items.
    $this->processor->processItems($actions, $event, $action->id());
  }

}
