<?php

namespace Drupal\business_rules\Controller;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Ajax\UpdateFlowchartCommand;
use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\Condition;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ScheduleTaskController.
 *
 * @package Drupal\business_rules\Controller
 */
class ScheduleTaskController extends ControllerBase {

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The type of item to be configured action|condition.
   *
   * @var string
   */
  protected $item;

  /**
   * The Item config name: condition | action.
   *
   * @var string
   */
  protected $itemType;

  /**
   * All saved items from database.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]|static[]
   */
  protected $items;

  /**
   * The item name for the configuration actions|conditions.
   *
   * @var string
   */
  protected $itemsName;

  /**
   * The item label.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $label;

  /**
   * The item label in plural.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $labelPlural;

  /**
   * The items currently saved on the Action.
   *
   * @var array
   */
  protected $savedItems = [];

  /**
   * Business Rules Util service.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  protected $util;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container) {
    $this->entityTypeManager = $container->get('entity_type.manager');
    $this->util              = $container->get('business_rules.util');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

  /**
   * Add item on Action.
   *
   * @param string $action_id
   *   The action id.
   * @param string $item_type
   *   The item type.
   * @param string $item_id
   *   The item id.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse.
   */
  public function addItem($action_id, $item_type, $item_id) {
    $action     = Action::load($action_id);
    $weight     = $this->getMaxItemWeight($action) + 1;
    $itemObj    = new BusinessRulesItemObject($item_id, $item_type, $weight);
    $items      = $action->getSettings('items');
    $item_array = $itemObj->toArray();

    $items[$itemObj->getId()] = $item_array[$itemObj->getId()];
    $action->setSetting('items', $items);
    $action->save();

    $url = new Url('entity.business_rules_action.edit_form', [
      'business_rules_action' => $action_id,
    ], ['fragment' => $item_id]);

    return new RedirectResponse($url->toString());
  }

  /**
   * Get the bigger weight for the action items.
   *
   * @param \Drupal\business_rules\ActionInterface $action
   *   The action to get the bigger item weight.
   *
   * @return int
   *   The bigger weight for the action items.
   */
  public function getMaxItemWeight(ActionInterface $action) {

    $items = $action->getSettings('items');

    $max = -100;
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
   * The items table.
   *
   * @param string $action_id
   *   The condition id.
   * @param string $item_type
   *   The item type.
   * @param string $method
   *   The method name: ajax|nojs.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Render array or AjaxResponse.
   */
  public function itemsTable($action_id, $item_type, $method) {
    $this->init($item_type);
    $action = Action::load($action_id);
    $this->removeCurrentItems($action_id, $this->items);

    $table['#title'] = $this->t('Add @label_plural on %action', [
      '%action'       => $action->label(),
      '@label_plural' => $this->labelPlural,
    ]);

    $table['#attached']['library'][] = 'system/drupal.system.modules';

    $table['filters'] = [
      '#type'       => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $table['filters']['text'] = [
      '#type'        => 'search',
      '#title'       => $this->t('Search'),
      '#size'        => 30,
      '#placeholder' => $this->t('Search for a @label key', ['@label' => $this->label]),
      '#attributes'  => [
        'class'        => ['table-filter-text'],
        'data-table'   => '.searchable-list',
        'autocomplete' => 'off',
        'title'        => $this->t('Enter a part of the @label key to filter by.', ['@label' => $this->label]),
      ],
    ];

    $header = [
      'label'       => $this->label,
      'id'          => $this->t('Machine Name'),
      'type'        => $this->t('Type'),
      'description' => $this->t('Description'),
      'operations'  => $this->t('Operations'),
      'filter'      => [
        'data'  => ['#markup' => 'filter'],
        'style' => 'display: none',
      ],
    ];

    $rows = [];
    /** @var \Drupal\business_rules\Entity\Action $item */
    foreach ($this->items as $item) {
      if (!in_array($item->id(), array_keys($this->savedItems)) && $item->id() != $action->id()) {

        $listBuilder = $this->entityTypeManager->getListBuilder($item->getEntityTypeId());
        $operations = $listBuilder->buildOperations($item);

        $search_string = $item->label() . ' ' .
          $item->id() . ' ' .
          $item->getTypeLabel() . ' ' .
          $item->getDescription();

        $link = Link::createFromRoute($item->label(), 'business_rules.schedule_task.items.add', [
          'action_id' => $action_id,
          'item_id'   => $item->id(),
          'item_type' => $item_type,
        ]);

        $rows[$item->id()] = [
          'label'       => ['data' => $link],
          'id'          => ['data' => ['#markup' => $item->id()]],
          'type'        => ['data' => ['#markup' => $item->getTypeLabel()]],
          'description' => ['data' => ['#markup' => $item->getDescription()]],
          'operations'  => ['data' => $operations],
          'filter'      => [
            'data'  => [['#markup' => '<span class="table-filter-text-source">' . $search_string . '</span>']],
            'style' => ['display: none'],
          ],
        ];
      }
    }

    $table['business_rule_items'] = [
      '#type'       => 'table',
      '#header'     => $header,
      '#rows'       => $rows,
      '#attributes' => [
        'class' => [
          'searchable-list',
        ],
      ],
    ];

    if ($method == 'ajax') {
      $table['#attached']['library'][] = 'core/drupal.dialog.ajax';

      $options = ['width' => '75%'];
      $title   = $this->t('Add @item', ['@item' => $this->label]);

      $response = new AjaxResponse();
      $response->addCommand(new OpenModalDialogCommand($title, $table, $options));

      return $response;
    }
    else {
      return $table;
    }
  }

  /**
   * Remove the action items.
   *
   * @param string $action_id
   *   The Action id.
   * @param array $items
   *   The items.
   */
  public function removeCurrentItems($action_id, &$items) {
    $action       = Action::load($action_id);
    $current      = $action->getSettings('items');
    $current_keys = array_keys($current);
    foreach ($items as $key => $value) {
      if (in_array($value->id(), $current_keys)) {
        unset($items[$key]);
      }
    }
  }

  /**
   * Init properties.
   *
   * @param string $item_type
   *   The item type action|condition.
   */
  public function init($item_type) {
    $this->item = $item_type;

    switch ($this->item) {
      case 'condition':
        $this->label       = $this->t('Condition');
        $this->labelPlural = $this->t('Conditions');
        $this->items       = Condition::loadMultiple();
        $this->itemsName   = 'conditions';
        $this->itemType    = 'condition';
        break;

      case 'action':
        $this->label       = $this->t('Action');
        $this->labelPlural = $this->t('Actions');
        $this->items       = Action::loadMultiple();
        $this->itemsName   = 'actions';
        $this->itemType    = 'action';
        break;

    }
  }

  /**
   * Remove item from condition.
   *
   * @param string $action_id
   *   The action id.
   * @param string $item_id
   *   The item id.
   * @param string $method
   *   The method name: ajax|nojs.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The AjaxResponse or the RedirectResponse.
   */
  public function removeItem($action_id, $item_id, $method) {
    $action = Action::load($action_id);
    $items = $action->getSettings('items');
    unset($items[$item_id]);
    $items = is_null($items) ? [] : $items;

    $action->setSetting('items', $items);
    $action->save();

    if ($method == 'ajax') {
      $chart_definition = $this->util->flowchart->getGraphDefinition($action);
      $textarea         = '<textarea id="graph_definition" style="display: none;">' . $chart_definition . '</textarea>';

      $response = new AjaxResponse();
      $response->addCommand(new RemoveCommand('#' . $item_id));
      $response->addCommand(new ReplaceCommand('#graph_definition', $textarea));
      $response->addCommand(new UpdateFlowchartCommand());

      return $response;
    }
    else {
      $url = new Url('entity.business_rules_action.edit_form', [
        'business_rule_action' => $action_id,
      ]);

      $string_url = $url->toString() . '#business_rule-add_buttons';

      return new RedirectResponse($string_url);
    }
  }

}
