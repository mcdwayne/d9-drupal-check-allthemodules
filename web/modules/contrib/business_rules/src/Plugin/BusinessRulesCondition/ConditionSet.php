<?php

namespace Drupal\business_rules\Plugin\BusinessRulesCondition;

use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Entity\Condition;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ConditionSet.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesCondition
 */
abstract class ConditionSet extends BusinessRulesConditionPlugin {

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
   * Return all others conditions with the same target Entity and Bundle.
   *
   * @param \Drupal\business_rules\Entity\Condition $condition
   *   The condition.
   *
   * @return array
   *   Array of conditions matched.
   */
  public static function getAvailableItems(Condition $condition) {
    $conditions         = Condition::loadMultiple();
    $current_conditions = $condition->getSettings('items');
    $conditions_matched = [];
    if (count($conditions)) {
      /** @var \Drupal\business_rules\Entity\Condition $c */
      foreach ($conditions as $c) {
        if ($condition->id() != $c->id() && self::checkInnerCondition($condition, $c) && !in_array($c->id(), array_keys($current_conditions))) {
          // Only condition s with no actions can be added to a condition set.
          if (!count($c->getSuccessItems()) && !count($c->getFailItems())) {
            $conditions_matched[] = $c;
          }
        }
      }
    }

    return $conditions_matched;
  }

  /**
   * Do not show conditionSets that already contains the main_condition.
   *
   * Important to avoid infinite condition check loops.
   *
   * @param \Drupal\business_rules\ConditionInterface $main_condition
   *   The main condition.
   * @param \Drupal\business_rules\ConditionInterface $child_condition
   *   The child condition.
   *
   * @return bool
   *   If check succeed or fails.
   */
  private static function checkInnerCondition(ConditionInterface $main_condition, ConditionInterface $child_condition) {
    $check = TRUE;
    if ($child_condition->getType() == 'condition_set') {
      $conditions = $child_condition->getSettings();
      foreach ($conditions as $condition) {
        $condition = Condition::load($condition['condition']);
        if ($main_condition->id() == $condition->id()) {
          $check = FALSE;
          break;
        }
        elseif ($condition->getType() == 'condition_set') {
          $inner_conditions = $condition->getSettings();
          foreach ($inner_conditions as $inner_condition) {
            $inner_condition = Condition::load($inner_condition['condition']);
            $check           = self::checkInnerCondition($condition, $inner_condition);
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
      $form_state->set('business_rule_condition', $item);

      return [];
    }

    // The conditions to process.
    $settings['items'] = [
      '#type'        => 'details',
      '#description' => t('Only conditions with no actions can be added to a condition set.'),
      '#title'       => t('Conditions'),
      '#open'        => TRUE,
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
   * @param \Drupal\business_rules\ItemInterface $condition
   *   The current condition.
   *
   * @return array
   *   The render array.
   */
  public function formItems(array $form, FormStateInterface $form_state, ItemInterface $condition) {
    $user_input = $form_state->getUserInput();

    $label     = t('Item');
    $raw_items = $condition->getSettings('items');

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
      '#empty'      => t('There are currently no conditions in this condition set. Add one by selecting an option below.'),
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

        $item = Condition::load($value->getId());

        $item_weight       = !empty($item) ? $value->getWeight() : '';
        $route_remove_item = 'business_rules.condition_set.items.remove';

        if (!empty($item)) {

          $key = $item->id();

          $listBuilder = $this->entityTypeManager->getListBuilder($item->getEntityTypeId());
          $operations = $listBuilder->buildOperations($item);

          $operations['#links']['remove'] = [
            'title'  => t('Remove'),
            'url'    => Url::fromRoute($route_remove_item, [
              'condition_id' => $condition->id(),
              'item_id'      => $item->id(),
              'method'       => 'nojs',
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

    $add_condition = Link::createFromRoute(t('Add Condition'), 'business_rules.condition_set.items.table', [
      'condition_id' => $condition->id(),
      'method'       => 'nojs',
    ], ['attributes' => ['class' => ['use-ajax']]]);

    $table['add'][] = [
      'data' => [
        'add' => [
          '#type'   => 'markup',
          '#markup' => $add_condition->toString(),
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
    $condition = $form_state->get('business_rule_condition');
    unset($form['variables']);
    if (!empty($condition) && $condition->isNew()) {
      $form['actions']['submit']['#value'] = t('Continue');
    }
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

}
