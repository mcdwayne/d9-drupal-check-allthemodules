<?php

namespace Drupal\business_rules\Controller;

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
use Zend\Diactoros\Response\RedirectResponse;

/**
 * Class ConditionsItemsController.
 *
 * @package Drupal\business_rules\Controller
 */
class ConditionsItemsController extends ControllerBase {

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Business Rules Flowchart.
   *
   * @var \Drupal\business_rules\Util\Flowchart\Flowchart
   */
  protected $flowchart;

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
   * The items currently saved on the Condition.
   *
   * @var array
   */
  protected $savedItems = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container) {
    $this->entityTypeManager = $container->get('entity_type.manager');
    $this->flowchart         = $container->get('business_rules.flowchart');
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
   * @param string $condition_item_type
   *   The condition item type: success|fail.
   * @param string $item_type
   *   The item type action|condition.
   * @param string $item_id
   *   The item id.
   *
   * @return \Zend\Diactoros\Response\RedirectResponse
   *   The RedirectResponse.
   */
  public function addItem($condition_id, $condition_item_type, $item_type, $item_id) {
    $condition = Condition::load($condition_id);
    $weight    = $condition->getMaxItemWeight($condition_item_type == 'success') + 1;
    $itemObj   = new BusinessRulesItemObject($item_id, $item_type, $weight);

    if ($condition_item_type == 'success') {
      $condition->addSuccessItem($itemObj);
    }
    else {
      $condition->addFailItem($itemObj);
    }

    $condition->save();

    $url = new Url('entity.business_rules_condition.edit_form', [
      'business_rules_condition' => $condition_id,
    ], ['fragment' => $condition_item_type . '-' . $item_id]);

    return new RedirectResponse($url->toString());
  }

  /**
   * The items table.
   *
   * @param string $condition_id
   *   The condition id.
   * @param string $condition_item_type
   *   The condition item type: success|fail.
   * @param string $item_type
   *   The item type action|condition.
   * @param string $method
   *   The method name: ajax|nojs.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Render array or AjaxResponse.
   */
  public function itemsTable($condition_id, $condition_item_type, $item_type, $method) {

    $this->init($item_type);
    /** @var \Drupal\business_rules\ConditionInterface $condition */
    $condition   = Condition::load($condition_id);
    $this->items = $condition->filterContextAvailableItems($this->items);

    if ($condition_item_type == 'success') {
      $this->savedItems = $condition->getSuccessItems();
    }
    elseif ($condition_item_type == 'fail') {
      $this->savedItems = $condition->getFailItems();
    }

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

    $table['help'] = [
      '#type'   => 'markup',
      '#markup' => $this->t('If the Condition is context dependent, only items with the same Entity/Bundle as the condition or with no context dependency are visible on this form.'),
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
    foreach ($this->items as $item) {
      if (!in_array($item->Id(), array_keys($this->savedItems)) && $item->id() != $condition->id()) {

        $listBuilder = $this->entityTypeManager->getListBuilder($item->getEntityTypeId());
        $operations = $listBuilder->buildOperations($item);

        $search_string = $item->label() . ' ' .
          $item->id() . ' ' .
          $item->getTypeLabel() . ' ' .
          $item->getDescription();

        $link = Link::createFromRoute($item->label(), 'business_rules.condition.items.add', [
          'condition_id'        => $condition_id,
          'condition_item_type' => $condition_item_type,
          'item_id'             => $item->Id(),
          'item_type'           => $item_type,
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
   * @param string $item_type
   *   The item type: action|condition.
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
   * @param string $condition_id
   *   The condition id.
   * @param string $condition_item_type
   *   The condition item type: success|fail.
   * @param string $item_type
   *   The item type action|condition.
   * @param string $item_id
   *   The item id.
   * @param string $method
   *   The method name: ajax|nojs.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Zend\Diactoros\Response\RedirectResponse
   *   The AjaxResponse or the RedirectResponse.
   */
  public function removeItem($condition_id, $condition_item_type, $item_type, $item_id, $method) {
    $condition = Condition::load($condition_id);
    $itemObj   = new BusinessRulesItemObject($item_id, $item_type, 0);

    if ($condition_item_type == 'success') {
      $condition->removeSuccessItem($itemObj);
    }
    else {
      $condition->removeFailItem($itemObj);
    }

    $condition->save();

    if ($method == 'ajax') {
      $chart_definition = $this->flowchart->getGraphDefinition($condition);
      $textarea         = '<textarea id="graph_definition" style="display: none;">' . $chart_definition . '</textarea>';

      $response = new AjaxResponse();
      $response->addCommand(new RemoveCommand('#' . $condition_item_type . '-' . $item_id));
      $response->addCommand(new ReplaceCommand('#graph_definition', $textarea));
      $response->addCommand(new UpdateFlowchartCommand());

      return $response;
    }
    else {
      $url = new Url('entity.business_rules_condition.edit_form', [
        'business_rule_condition' => $condition_id,
      ]);

      $string_url = $url->toString() . '#' . $condition_item_type . '-business_rule-add_buttons';

      return new RedirectResponse($string_url);
    }
  }

}
