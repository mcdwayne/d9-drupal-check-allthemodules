<?php

namespace Drupal\business_rules\Controller;

use Drupal\business_rules\Ajax\UpdateFlowchartCommand;
use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Entity\Condition;
use Drupal\business_rules\Plugin\BusinessRulesCondition\ConditionSet;
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
 * Class ConditionSetController.
 *
 * @package Drupal\business_rules\Controller
 */
class ConditionSetController extends ControllerBase {

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
   * Add item on Condition.
   *
   * @param string $condition_id
   *   The condition id.
   * @param string $item_id
   *   The item.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse.
   */
  public function addItem($condition_id, $item_id) {
    $condition  = Condition::load($condition_id);
    $weight     = $this->getMaxItemWeight($condition) + 1;
    $itemObj    = new BusinessRulesItemObject($item_id, 'condition', $weight);
    $items      = $condition->getSettings('items');
    $item_array = $itemObj->toArray();

    $items[$itemObj->getId()] = $item_array[$itemObj->getId()];
    $condition->setSetting('items', $items);
    $condition->save();

    $url = new Url('entity.business_rules_condition.edit_form', [
      'business_rules_condition' => $condition_id,
    ], ['fragment' => $item_id]);

    return new RedirectResponse($url->toString());
  }

  /**
   * Get the bigger weight for the condition items.
   *
   * @param \Drupal\business_rules\ConditionInterface $condition
   *   The condition to get the bigger item weight.
   *
   * @return int
   *   The bigger weight for the condition items.
   */
  public function getMaxItemWeight(ConditionInterface $condition) {

    $items = $condition->getSettings('items');

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
   * @param string $condition_id
   *   The condition id.
   * @param string $method
   *   The method name: ajax|nojs.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Render array or AjaxResponse.
   */
  public function itemsTable($condition_id, $method) {

    $condition = Condition::load($condition_id);
    $this->init($condition);

    $table['#title'] = $this->t('Add @label_plural on %condition', [
      '%condition'    => $condition->label(),
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
    /** @var \Drupal\business_rules\Entity\Condition $item */
    foreach ($this->items as $item) {
      if (!in_array($item->id(), array_keys($this->savedItems)) && $item->id() != $condition->id()) {

        $listBuilder = $this->entityTypeManager->getListBuilder($item->getEntityTypeId());
        $operations = $listBuilder->buildOperations($item);

        $search_string = $item->label() . ' ' .
          $item->id() . ' ' .
          $item->getTypeLabel() . ' ' .
          $item->getDescription();

        $link = Link::createFromRoute($item->label(), 'business_rules.condition_set.items.add', [
          'condition_id' => $condition_id,
          'item_id'      => $item->id(),
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
   * Init properties.
   *
   * @param \Drupal\business_rules\Entity\Condition $condition
   *   The ActionSet action.
   */
  public function init(Condition $condition) {
    $this->label       = $this->t('Condition');
    $this->labelPlural = $this->t('Conditions');
    $this->items       = ConditionSet::getAvailableItems($condition);
    $this->itemsName   = 'conditions';
    $this->itemType    = 'condition';
  }

  /**
   * Remove item from condition.
   *
   * @param string $condition_id
   *   The condition id.
   * @param string $item_id
   *   The item id.
   * @param string $method
   *   The method name: ajax|nojs.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The AjaxResponse or the RedirectResponse.
   */
  public function removeItem($condition_id, $item_id, $method) {
    $condition = Condition::load($condition_id);
    $items     = $condition->getSettings('items');
    unset($items[$item_id]);
    $items = is_null($items) ? [] : $items;

    $condition->setSetting('items', $items);
    $condition->save();

    if ($method == 'ajax') {
      $chart_definition = $this->util->flowchart->getGraphDefinition($condition);
      $textarea         = '<textarea id="graph_definition" style="display: none;">' . $chart_definition . '</textarea>';

      $response = new AjaxResponse();
      $response->addCommand(new RemoveCommand('#' . $item_id));
      $response->addCommand(new ReplaceCommand('#graph_definition', $textarea));
      $response->addCommand(new UpdateFlowchartCommand());

      return $response;
    }
    else {
      $url = new Url('entity.business_rules_condition.edit_form', [
        'business_rule_condition' => $condition_id,
      ]);

      $string_url = $url->toString() . '#business_rule-add_buttons';

      return new RedirectResponse($string_url);
    }
  }

}
