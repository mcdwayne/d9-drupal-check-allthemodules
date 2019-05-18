<?php

namespace Drupal\business_rules\Controller;

use Drupal\business_rules\Ajax\UpdateFlowchartCommand;
use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\BusinessRule;
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
 * Class BusinessRulesItemsController.
 *
 * @package Drupal\business_rules\Controller
 */
class BusinessRulesItemsController extends ControllerBase {

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
   * The type of item to be configured.
   *
   * @var string
   *   condition|action
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
   * The add new item button.
   *
   * @var \Drupal\Core\Link
   */
  protected $newItemButton;

  /**
   * The items currently saved on the Rule.
   *
   * @var array
   */
  protected $savedItems = [];

  /**
   * The Business Rules Util.
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
    $this->flowchart         = $container->get('business_rules.flowchart');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

  /**
   * Add one item on the Business Rule.
   *
   * @param string $business_rule
   *   The business rule id.
   * @param string $item_type
   *   The item type.
   * @param string $item_id
   *   The item id.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse.
   */
  public function addItem($business_rule, $item_type, $item_id) {
    $rule    = BusinessRule::load($business_rule);
    $weight  = $rule->getItemMaxWeight() + 1;
    $itemObj = new BusinessRulesItemObject($item_id, $item_type, $weight);
    $rule->addItem($itemObj);
    $rule->save();

    $url        = new Url('entity.business_rule.edit_form', [
      'business_rule' => $business_rule,
    ]);
    $string_url = $url->toString() . '#' . $item_id;

    return new RedirectResponse($string_url);
  }

  /**
   * The items table.
   *
   * @param string $business_rule
   *   The Business Rule id.
   * @param string $item_type
   *   The item type.
   * @param string $method
   *   The method nojs|ajax.
   *
   * @return array|AjaxResponse
   *   Render array or AjaxResponse.
   */
  public function itemsTable($business_rule, $item_type, $method) {
    $this->init($item_type);
    /** @var \Drupal\business_rules\Entity\BusinessRule $rule */
    $rule             = BusinessRule::load($business_rule);
    $this->items      = $rule->filterContextAvailableItems($this->items);
    $this->savedItems = $rule->getItems();

    $table['#title'] = $this->t('Add @label_plural on %rule', [
      '%rule'         => $rule->label(),
      '@label_plural' => $this->labelPlural,
    ]);

    $table['#attached']['library'][] = 'system/drupal.system.modules';

    $table['add_new'] = [
      '#type'   => 'markup',
      '#markup' => $this->newItemButton->toString(),
      '#prefix' => '<ul class="action-links"><li>',
      '#suffix' => '</li></ul>',
    ];

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
      '#markup' => $this->t('If the Business Rule has Target Entity Type and/or Target Bundle, only items with the same Entity/Bundle as the rule or with no context dependency are visible on this form.'),
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
      if (!in_array($item->id(), array_keys($this->savedItems))) {

        $listBuilder = $this->entityTypeManager->getListBuilder($item->getEntityTypeId());
        $operations = $listBuilder->buildOperations($item);
        foreach ($operations['#links'] as $i => $link) {
          $operations['#links'][$i]['url']->setRouteParameter('destination', $this->util->getPreviousUri()
            ->toString());
        }

        $search_string = $item->label() . ' ' .
          $item->id() . ' ' .
          $item->getTypeLabel() . ' ' .
          $item->getDescription();

        $link = Link::createFromRoute($item->label(), 'business_rules.items.add', [
          'business_rule' => $business_rule,
          'item_id'       => $item->Id(),
          'item_type'     => $item_type,
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
   *   The item type action|condition.
   */
  public function init($item_type) {
    $this->item = $item_type;

    try {
      $destination = stristr($this->util->getPreviousUri()->toString(), '/ajax/') ? [] : ['destination' => $this->util->getPreviousUri()->toString()];
    }
    catch (\Exception $e) {
      $destination = [];
    }

    switch ($this->item) {
      case 'condition':
        $this->label         = $this->t('Condition');
        $this->labelPlural   = $this->t('Conditions');
        $this->items         = Condition::loadMultiple();
        $this->itemsName     = 'conditions';
        $this->itemType      = 'condition';
        $this->newItemButton = Link::createFromRoute($this->t('Add Condition'),
          'entity.business_rules_condition.add_form',
          $destination,
          [
            'attributes' => [
              'class' => [
                'button',
                'button-action',
                'button--primary',
                'button--small',
              ],
            ],
          ]
        );
        break;

      case 'action':
        $this->label         = $this->t('Action');
        $this->labelPlural   = $this->t('Actions');
        $this->items         = Action::loadMultiple();
        $this->itemsName     = 'actions';
        $this->itemType      = 'action';
        $this->newItemButton = Link::createFromRoute($this->t('Add Action'),
          'entity.business_rules_action.add_form',
          $destination,
          [
            'attributes' => [
              'class' => [
                'button',
                'button-action',
                'button--primary',
                'button--small',
              ],
            ],
          ]
        );
        break;

    }
  }

  /**
   * Remove one item from the Business Rule.
   *
   * @param string $business_rule
   *   The business rule id.
   * @param string $item_type
   *   The item type: action|condition.
   * @param string $item_id
   *   The item id.
   * @param string $method
   *   The method ajax|nojs.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The AjaxResponse or the RedirectResponse.
   */
  public function removeItem($business_rule, $item_type, $item_id, $method) {
    $rule    = BusinessRule::load($business_rule);
    $itemObj = new BusinessRulesItemObject($item_id, $item_type, 0);
    $rule->removeItem($itemObj);
    $rule->save();

    if ($method == 'ajax') {
      $chart_definition = $this->flowchart->getGraphDefinition($rule);
      $textarea         = '<textarea id="graph_definition" style="display: none;">' . $chart_definition . '</textarea>';

      $response = new AjaxResponse();
      $response->addCommand(new RemoveCommand('#' . $item_id));
      $response->addCommand(new ReplaceCommand('#graph_definition', $textarea));
      $response->addCommand(new UpdateFlowchartCommand());

      return $response;
    }
    else {
      $url = new Url('entity.business_rule.edit_form', [
        'business_rule' => $business_rule,
      ]);

      $string_url = $url->toString() . '#business_rule-add_buttons';

      return new RedirectResponse($string_url);
    }
  }

}
