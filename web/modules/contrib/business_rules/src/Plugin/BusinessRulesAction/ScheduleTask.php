<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\Condition;
use Drupal\business_rules\Entity\Schedule;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ScheduleTask.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "schedule_a_task",
 *   label = @Translation("Schedule a task"),
 *   group = @Translation("Schedule"),
 *   description = @Translation("Schedule a task to the future"),
 *   isContextDependent = TRUE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = TRUE,
 * )
 */
class ScheduleTask extends BusinessRulesActionPlugin {

  private $timeUnitOptions;

  /**
   * We don't want to use the same wait two times for an item.
   *
   * @var array
   */
  private $usedWeight = [];

  /**
   * Business Rules item.
   *
   * @var \Drupal\business_rules\ItemInterface*/
  private $item;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->timeUnitOptions = [
      'seconds' => t('Seconds'),
      'minutes' => t('Minutes'),
      'hours'   => t('Hours'),
      'days'    => t('Days'),
      'weeks'   => t('Weeks'),
      'months'  => t('Months'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    // Only show settings form if the item is already saved.
    if ($item->isNew()) {
      return [];
    }

    $settings['time_offset'] = [];
    $settings['time_unit']   = [];

    $settings['identifier'] = [
      '#type'          => 'textfield',
      '#title'         => t('Identifier'),
      '#description'   => t('Use this string to identity the task. Any existing task for this action will be replaced. You can use tokens or variables to make this field unique.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('identifier'),
    ];

    $settings['scheduler']                = [
      '#type'        => 'fieldset',
      '#title'       => t('Scheduler'),
      '#description' => t('How much time before or after the date field above do you want to trigger the schedule?'),
      '#attributes'  => ['class' => ['display-flex', 'fieldgroup']],
    ];
    $settings['scheduler']['time_offset'] = [
      '#type'          => 'textfield',
      '#title'         => t('Time offset'),
      '#default_value' => $item->getSettings('time_offset'),
      '#required'      => TRUE,
      '#size'          => 8,
      '#prefix'        => '<div class="padding-right-20">',
      '#suffix'        => '</div>',
    ];

    $settings['scheduler']['time_unit'] = [
      '#type'          => 'select',
      '#title'         => t('Time Unit'),
      '#options'       => $this->timeUnitOptions,
      '#default_value' => $item->getSettings('time_unit'),
      '#required'      => TRUE,
      '#prefix'        => '<div>',
      '#suffix'        => '</div>',
    ];

    // Filter fields option to present only date time and timestam options.
    $fields = &$form['settings']['field']['#options'];
    foreach ($fields as $key => $field) {
      $type = $field->getArguments()['@type'];
      if (!in_array($type, ['changed', 'created', 'timestamp', 'datetime'])) {
        unset($fields[$key]);
      }
    }

    $settings['update_entity'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Save entity as the last action of the task.'),
      '#description'   => t('Check this option if you are changing values on the entity and you want to persist those changes on the database.'),
      '#default_value' => $item->getSettings('update_entity'),
    ];

    $form['settings']['field']['#description'] = t('Entity changed, created, timestamp or datetime field.');

    // The items to process.
    $settings['items'] = [
      '#type'        => 'details',
      '#description' => t('The items are evaluated on the presented order. Drag them to change the order.'),
      '#title'       => t('Items'),
      '#open'        => TRUE,
    ];

    $this->item          = $item;
    $settings['items'][] = $this->formItems($form, $form_state, $item);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\business_rules\ItemInterface $item */
    $item = $form_state->get('business_rules_item');

    // We only can validate the form if the item is not new.
    if (!empty($item) && !$item->isNew()) {

      // Validate Time offset.
      if (!is_numeric($form_state->getValue('time_offset'))) {
        $form_state->setErrorByName('time_offset', t('Time offset must be a number.'));
      }

      // Validate target field.
      $entity_type = $form_state->getValue('target_entity_type');
      $bundle      = $form_state->getValue('target_bundle');
      $field       = $form_state->getValue('field');
      $fields      = $this->util->getBundleFields($entity_type, $bundle);
      if (isset($fields[$field]) && !in_array($fields[$field]->getArguments()['@type'],
          [
            'changed',
            'created',
            'timestamp',
            'datetime',
          ])
      ) {
        $form_state->setErrorByName('field', t('Field type must be type of: "changed", "created", "timestamp" or "datetime"'));
      }
    }
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

        if ($value->getType() == 'condition') {
          $item = Condition::load($value->getId());
        }
        elseif ($value->getType() == 'action') {
          $item = Action::load($value->getId());
        }

        $item_weight       = !empty($item) ? $value->getWeight() : '';
        $route_remove_item = 'business_rules.schedule_task.items.remove';

        if (!empty($item)) {

          $key = $item->id();

          $listBuilder = $this->util->container->get('entity_type.manager')
            ->getListBuilder($item->getEntityTypeId());
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

    $add_action = Link::createFromRoute(t('Add Action'), 'business_rules.schedule_task.items.table', [
      'action_id' => $action->id(),
      'item_type' => 'action',
      'method'    => 'nojs',
    ], ['attributes' => ['class' => ['use-ajax']]]);

    $add_condition = Link::createFromRoute(t('Add Condition'), 'business_rules.schedule_task.items.table', [
      'action_id' => $action->id(),
      'item_type' => 'condition',
      'method'    => 'nojs',
    ], ['attributes' => ['class' => ['use-ajax']]]);

    $table['add'][] = [
      'data' => [
        'add' => [
          '#type'   => 'markup',
          '#markup' => $add_condition->toString() . ' | ' . $add_action->toString(),
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
    $time_offset = $action->getSettings('time_offset');
    $time_unit = $action->getSettings('time_unit');
    $update_entity = $action->getSettings('update_entity');

    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $event->getSubject();
    $field = $entity->get($action->getSettings('field'))->value;

    $scheduled_date = FALSE;
    if (is_int($field)) {
      // Add number of seconds to timestamp data type.
      switch ($time_unit) {
        case 'seconds':
          $scheduled_date = $field + $time_offset;
          break;

        case 'minutes':
          $scheduled_date = $field + ($time_offset * 60);
          break;

        case 'hours':
          $scheduled_date = $field + ($time_offset * 60 * 60);
          break;

        case 'days':
          $scheduled_date = $field + ($time_offset * 60 * 60 * 24);
          break;

        case 'weeks':
          $scheduled_date = $field + ($time_offset * 60 * 60 * 24 * 7);
          break;

        case 'months':
          $date           = new \DateTime($field);
          $interval       = new \DateInterval('');
          $interval->m    = $time_offset;
          $scheduled_date = $date->add($interval)->getTimestamp();
          break;
      }
    }

    $schedule = Schedule::loadByNameAndTriggeredBy($action->getSettings('identifier'), $action->id());
    $schedule->setName($action->getSettings('identifier'));
    $schedule->setDescription($action->getSettings('description'));
    if ($scheduled_date) {
      $schedule->setScheduled($scheduled_date);
    }
    $schedule->setExecuted(0);
    $schedule->setTriggeredBy($action);
    $schedule->setUpdateEntity($update_entity);
    $schedule->setEvent($event);

    $schedule->save();
  }

}
