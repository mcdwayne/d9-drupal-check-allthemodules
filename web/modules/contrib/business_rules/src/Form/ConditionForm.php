<?php

namespace Drupal\business_rules\Form;

use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\Condition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ConditionForm.
 *
 * @package Drupal\business_rules\Form
 */
class ConditionForm extends ItemForm {

  /**
   * We don't want to use the same wait two times for an item.
   *
   * @var array
   */
  private $usedWeight = [];

  /**
   * {@inheritdoc}
   */
  public function getItemManager() {
    $container = \Drupal::getContainer();

    return $container->get('plugin.manager.business_rules.condition');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if (!$this->entity->isNew()) {

      $form['reverse'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Reverse value'),
        '#description'   => $this->t("It's equivalent to (NOT) operator. Invert the condition result from TRUE to FALSE and from FALSE to TRUE."),
        '#default_value' => $this->entity->isReverse(),
        '#weight'        => 31,
      ];

      // Do not show success/fail items if it is part of a condition set.
      if (!$this->isPartOfConditionSet($this->entity)) {

        $form['additional_fields']['success'] = [
          '#type'       => 'details',
          '#title'      => $this->t('Items to execute if condition succeed'),
          '#open'       => TRUE,
          '#attributes' => [
            'class' => ['success'],
          ],
        ];

        $form['additional_fields']['success']['items'] = $this->formItems($form, $form_state, 'success');

        $form['additional_fields']['fail'] = [
          '#type'       => 'details',
          '#title'      => $this->t('Items to execute if condition fails'),
          '#open'       => TRUE,
          '#attributes' => [
            'class' => ['fail'],
          ],
        ];

        $form['additional_fields']['fail']['items'] = $this->formItems($form, $form_state, 'fail');

        $form['#attached']['library'][] = 'business_rules/style';
      }
      else {
        $form['no_items'] = [
          '#type'        => 'details',
          '#title'       => $this->t('No items available'),
          '#description' => $this->t('As this condition is part of a condition set (AND|OR), you can not add success/fail items here. Use the condition set instead.'),
          '#open'        => TRUE,
          '#weight'      => 900,
        ];

        unset($form['flowchart']);
      }
    }

    return $form;
  }

  /**
   * Check if one condition is part of a conditions set.
   *
   * @param \Drupal\business_rules\Entity\Condition $condition
   *   The condition.
   *
   * @return bool
   *   TRUE|FALSE.
   */
  private function isPartOfConditionSet(Condition $condition) {
    $conditions = Condition::loadMultipleByType('logical_and');
    $conditions = array_merge($conditions, Condition::loadMultipleByType('logical_or'));

    $items = [];
    /** @var \Drupal\business_rules\Entity\Condition $c */
    foreach ($conditions as $c) {
      if (is_array($c->getSettings('items'))) {
        $items = array_merge($items, $c->getSettings('items'));
      }
    }

    foreach ($items as $item) {
      if ($item['type'] == 'condition') {
        if ($this->entity->id() == $item['id']) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Provide the form fields for add Business Rule's Items.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param string $items_type
   *   The items type: success|fail.
   *
   * @return array
   *   The render array.
   */
  public function formItems(array $form, FormStateInterface $form_state, $items_type) {

    /** @var \Drupal\business_rules\Entity\Condition $condition */
    $condition = $this->entity;

    $user_input = $form_state->getUserInput();

    $label        = $this->t('Item');
    $label_plural = $this->t('Items');
    if ($items_type == 'success') {
      $items = $condition->getSuccessItems();
    }
    elseif ($items_type == 'fail') {
      $items = $condition->getFailItems();
    }

    uasort($items, function ($a, $b) {
      return $a->getWeight() < $b->getWeight() ? -1 : 1;
    });

    $header = [
      'type'                    => $this->t('Type'),
      'label'                   => $label,
      'weight'                  => $this->t('Weight'),
      'id'                      => $this->t('Machine name'),
      'subtype'                 => $this->t('Subtype'),
      'description'             => $this->t('Description'),
      'operations'              => $this->t('Operations'),
      'business_rule_item_type' => [
        'data'  => '',
        'width' => '0px',
      ],
    ];

    $table[$items_type] = [
      '#type'       => 'table',
      '#header'     => $header,
      '#attributes' => ['id' => 'business_rules-items-' . $items_type],
      '#empty'      => $this->t('There are currently no @label in this condition. Add one by selecting an option below.', ['@label' => $label_plural]),
      '#tabledrag'  => [
        [
          'action'       => 'order',
          'relationship' => 'sibling',
          'group'        => $items_type . '-items-order-weight',
        ],
      ],
    ];

    if (is_array($items)) {
      foreach ($items as $value) {

        if ($value->getType() == 'condition') {
          $item = Condition::load($value->getId());
        }
        elseif ($value->getType() == 'action') {
          $item = Action::load($value->getId());
        }

        $item_weight       = !empty($item) ? $value->getWeight() : '';
        $route_remove_item = 'business_rules.condition.items.remove';

        if (!empty($item)) {

          $key = $item->id();

          $listBuilder = $this->entityTypeManager->getListBuilder($item->getEntityTypeId());
          $operations = $listBuilder->buildOperations($item);

          $operations['#links']['remove'] = [
            'title'  => $this->t('Remove'),
            'url'    => Url::fromRoute($route_remove_item, [
              'condition_id'        => $condition->id(),
              'condition_item_type' => $items_type,
              'item_type'           => $value->getType(),
              'item_id'             => $item->id(),
              'method'              => 'nojs',
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
            '#title'         => $this->t('Weight for item'),
            '#title_display' => 'invisible',
            '#delta'         => 100,
            '#default_value' => $item_weight,
            '#attributes'    => [
              'class' => [$items_type . '-items-order-weight'],
            ],
          ];

          $table[$items_type][$key] = [
            '#attributes'             => [
              'class' => 'draggable',
              'id'    => $items_type . '-' . $item->id(),
            ],
            '#weight'                 => isset($user_input['effects']) ? $user_input['effects'][$key]['weight'] : NULL,
            'type'                    => ['#markup' => $item->getBusinessRuleItemTranslatedType()],
            'item'                    => [
              '#tree' => FALSE,
              'label' => ['#markup' => $item->label()],
            ],
            'weight'                  => $weight,
            'id'                      => ['#markup' => $item->id()],
            'subtype'                 => ['#markup' => $item->getTypeLabel()],
            'description'             => ['#markup' => $item->getDescription()],
            'operations'              => $operations,
            'business_rule_item_type' => [
              '#type'  => 'value',
              '#value' => $value->getType(),
            ],
          ];

        }
      }
    }

    $add_condition = Link::createFromRoute($this->t('Add Condition'), 'business_rules.condition.items.table', [
      'condition_id'        => $condition->id(),
      'item_type'           => 'condition',
      'condition_item_type' => $items_type,
      'method'              => 'nojs',
    ], ['attributes' => ['class' => ['use-ajax']]]);
    $add_action    = Link::createFromRoute($this->t('Add Action'), 'business_rules.condition.items.table', [
      'condition_id'        => $condition->id(),
      'item_type'           => 'action',
      'condition_item_type' => $items_type,
      'method'              => 'nojs',
    ], ['attributes' => ['class' => ['use-ajax']]]);

    $table['add'][] = [
      'data' => [
        'add' => [
          '#type'   => 'markup',
          '#markup' => $add_condition->toString() . ' | ' . $add_action->toString(),
          '#prefix' => '<div id="' . $items_type . '-business_rule-add_buttons">',
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
   * Additional steps to save condition's items.
   *
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\business_rules\Entity\Condition $condition */
    $condition = $this->entity;
    if (!$condition->isNew()) {
      $success_items = $form_state->getValue('success');
      $fail_items    = $form_state->getValue('fail');
      $condition->set('success_items', []);
      $condition->set('fail_items', []);
      $new_success_items = [];
      $new_fail_items    = [];
      if (is_array($success_items)) {
        foreach ($success_items as $key => $value) {
          $itemObj = new BusinessRulesItemObject($key, $value['business_rule_item_type'], $value['weight']);

          $new_success_items[$value['weight']] = $itemObj;
        }
      }

      if (is_array($fail_items)) {
        foreach ($fail_items as $key => $value) {
          $itemObj = new BusinessRulesItemObject($key, $value['business_rule_item_type'], $value['weight']);

          $new_fail_items[$value['weight']] = $itemObj;
        }
      }

      ksort($new_success_items);
      foreach ($new_success_items as $item) {
        $condition->addSuccessItem($item);
      }

      ksort($new_fail_items);
      foreach ($new_fail_items as $item) {
        $condition->addFailItem($item);
      }

      $condition->save();
    }

    return parent::save($form, $form_state);
  }

}
