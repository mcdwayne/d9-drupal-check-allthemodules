<?php

namespace Drupal\business_rules\Util;

use Drupal\business_rules\ActionListBuilder;
use Drupal\business_rules\BusinessRuleListBuilder;
use Drupal\business_rules\ConditionListBuilder;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\BusinessRule;
use Drupal\business_rules\Entity\BusinessRulesItemBase;
use Drupal\business_rules\Entity\Condition;
use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\VariableListBuilder;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BusinessRulesUtil.
 *
 * @package Drupal\business_rules\Util
 */
class BusinessRulesUtil {

  use StringTranslationTrait;

  const BIGGER = '>';

  const BIGGER_OR_EQUALS = '>=';

  const SMALLER = '<';

  const SMALLER_OR_EQUALS = '<=';

  const EQUALS = '==';

  const DIFFERENT = '!=';

  const IS_EMPTY = 'empty';

  const CONTAINS = 'contains';

  const STARTS_WITH = 'starts_with';

  const ENDS_WITH = 'ends_with';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * Drupal Container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public $container;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  public $entityFieldManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  public $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  public $fieldTypePluginManager;

  /**
   * The Business Rules Flowchart.
   *
   * @var \Drupal\business_rules\Util\Flowchart\Flowchart
   */
  public $flowchart;

  /**
   * The Business Rules logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  public $logger;

  /**
   * The ModuleHandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  public $request;

  /**
   * The Drupal token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  public $token;

  /**
   * The token tree builder.
   *
   * @var \Drupal\token\TreeBuilderInterface
   */
  public $tokenTree;

  /**
   * The KeyValueExpirableFactory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $keyValueExpirable;

  /**
   * The variable manager.
   *
   * @var \Drupal\business_rules\Plugin\BusinessRulesVariableManager
   */
  protected $variableManager;

  /**
   * BusinessRulesUtil constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The services container.
   */
  public function __construct(ContainerInterface $container) {

    $this->container              = $container;
    $this->entityFieldManager     = $container->get('entity_field.manager');
    $this->fieldTypePluginManager = $container->get('plugin.manager.field.field_type');
    $this->entityTypeBundleInfo   = $container->get('entity_type.bundle.info');
    $this->configFactory          = $container->get('config.factory');
    $this->entityTypeManager      = $container->get('entity_type.manager');
    $this->entityTypeBundleInfo   = $container->get('entity_type.bundle.info');
    $this->variableManager        = $container->get('plugin.manager.business_rules.variable');
    $this->request                = $container->get('request_stack')
      ->getCurrentRequest();
    $this->logger                 = $container->get('logger.factory')
      ->get('business_rules');
    $this->keyValueExpirable      = $container->get('keyvalue.expirable');
    $this->flowchart              = $container->get('business_rules.flowchart');
    $this->moduleHandler          = $container->get('module_handler');
    $this->token                  = $container->get('token');

    if ($this->moduleHandler->moduleExists('token')) {
      $this->tokenTree = $container->get('token.tree_builder');
    }
    else {
      $this->tokenTree = NULL;
    }
  }

  /**
   * Criteria checker.
   *
   * @param string $value1
   *   The value to be compared.
   * @param string $operator
   *   The operator.
   * @param string $value2
   *   The value to test against.
   *
   * @return bool
   *   Criteria met/not met.
   */
  public function criteriaMet($value1, $operator, $value2) {
    switch ($operator) {
      case self::EQUALS:
        if ($value1 === $value2) {
          return TRUE;
        }
        break;

      case self::CONTAINS:
        if (strpos($value1, $value2) !== FALSE) {
          return TRUE;
        }
        break;

      case self::BIGGER:
        if ($value1 > $value2) {
          return TRUE;
        }
        break;

      case self::BIGGER_OR_EQUALS:
        if ($value1 >= $value2) {
          return TRUE;
        }
        break;

      case self::SMALLER:
        if ($value1 < $value2) {
          return TRUE;
        }
        break;

      case self::SMALLER_OR_EQUALS:
        if ($value1 <= $value2) {
          return TRUE;
        }
        break;

      case self::DIFFERENT:
        if ($value1 != $value2) {
          return TRUE;
        }
        break;

      case self::IS_EMPTY:
        if (empty($value1)) {
          return TRUE;
        }
        break;

      case self::STARTS_WITH:
        if (strpos($value1, $value2) === 0) {
          return TRUE;
        }
        break;

      case self::ENDS_WITH:
        if (substr($value1, strlen($value2) * -1) === $value2) {
          return TRUE;
        }
        break;

      default:
        return FALSE;
    }

    return FALSE;
  }

  /**
   * Get an render array for add items form.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The Business Rule Item.
   * @param array $items
   *   The array of items to render inside the table form.
   * @param array $selected_items
   *   The current selected items.
   * @param string $label
   *   The item label.
   * @param string $label_plural
   *   The item label in plural.
   * @param \Drupal\Core\Url $back_url
   *   The return Url.
   *
   * @return array
   *   The render array.
   */
  public function getAddItemsForm(ItemInterface $item, array $items, array $selected_items, $label, $label_plural, Url $back_url) {
    $form['#title'] = $this->t('Add @label_plural on %parent', [
      '%parent'       => $item->label(),
      '@label_plural' => $label_plural,
    ]);

    $form['#attached']['library'][] = 'system/drupal.system.modules';

    $form['filters'] = [
      '#type'       => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $form['filters']['text'] = [
      '#type'        => 'search',
      '#title'       => $this->t('Search'),
      '#size'        => 30,
      '#placeholder' => $this->t('Search for a @label key', ['@label' => $label]),
      '#attributes'  => [
        'class'        => ['table-filter-text'],
        'data-table'   => '.searchable-list',
        'autocomplete' => 'off',
        'title'        => $this->t('Enter a part of the @label key to filter by.', ['@label' => $label]),
      ],
    ];

    $header = [
      'label'       => $label,
      'id'          => $this->t('Machine Name'),
      'type'        => $this->t('Type'),
      'description' => $this->t('Description'),
      'filter'      => [
        'data'  => ['#markup' => 'filter'],
        'style' => 'display: none',
      ],
    ];

    $rows = [];

    foreach ($items as $item) {
      $search_string = $item->label() . ' ' .
        $item->id() . ' ' .
        $item->getTypeLabel() . ' ' .
        $item->getDescription();

      $rows[$item->id()] = [
        'label'       => ['data' => ['#markup' => $item->label()]],
        'id'          => ['data' => ['#markup' => $item->id()]],
        'type'        => ['data' => ['#markup' => $item->getTypeLabel()]],
        'description' => ['data' => ['#markup' => $item->getDescription()]],
        'filter'      => [
          'data'  => [['#markup' => '<span class="table-filter-text-source">' . $search_string . '</span>']],
          'style' => ['display: none'],
        ],
      ];
    }

    $form['items'] = [
      '#type'          => 'tableselect',
      '#header'        => $header,
      '#options'       => $rows,
      '#js_select'     => FALSE,
      '#default_value' => $selected_items,
      '#attributes'    => [
        'class' => [
          'searchable-list',
        ],
      ],
    ];

    $form['actions'] = [
      '#type'  => 'actions',
      'submit' => [
        '#type'        => 'submit',
        '#value'       => $this->t('Save'),
        '#button_type' => 'primary',
      ],
      'back'   => [
        '#type'        => 'link',
        '#title'       => $this->t('Back'),
        '#button_type' => 'danger',
        '#attributes'  => ['class' => ['button', 'button--danger']],
        '#url'         => $back_url,
      ],
    ];

    return $form;
  }

  /**
   * Helper function to return all fields from one bundle.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return array
   *   Array of fields ['type' => 'description']
   */
  public function getBundleFields($entityType, $bundle) {

    if (empty($entityType) || empty($bundle)) {
      return [];
    }

    $fields = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);
    foreach ($fields as $field_name => $field_storage) {
      $field_type           = $field_storage->getType();
      $options[$field_name] = $this->t('@type: @field', [
        '@type'  => $field_type,
        '@field' => $field_storage->getLabel() . " [$field_name]",
      ]);

    }
    asort($options);

    return $options;
  }

  /**
   * Helper function to return all editable fields from one bundle.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param array $field_types_ids
   *   Array of field types ids if you want to get specifics field types.
   *
   * @return array
   *   Array of fields ['type' => 'description']
   */
  public function getBundleEditableFields($entityType, $bundle, array $field_types_ids = []) {

    if (empty($entityType) || empty($bundle)) {
      return [];
    }

    $fields      = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);
    $field_types = $this->fieldTypePluginManager->getDefinitions();
    $options     = [];
    foreach ($fields as $field_name => $field_storage) {

      // Do not show: non-configurable field storages but title.
      $field_type = $field_storage->getType();
      if (($field_storage instanceof FieldConfig || ($field_storage instanceof BaseFieldDefinition && $field_name == 'title'))
      ) {
        if (count($field_types_ids) == 0 || in_array($field_type, $field_types_ids)) {
          $options[$field_name] = $this->t('@type: @field', [
            '@type'  => $field_types[$field_type]['label'],
            '@field' => $field_storage->getLabel() . " [$field_name]",
          ]);
        }
      }

    }
    asort($options);

    return $options;
  }

  /**
   * Return an array with all bundles related to one content type.
   *
   * @param string $entity_type
   *   The content type ID.
   *
   * @return array
   *   Array of bundles.
   */
  public function getBundles($entity_type) {
    $output = [
      '' => $this->t('- Select -'),
    ];

    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    foreach ($bundles as $key => $value) {
      $output[$key] = $value['label'];
    }

    asort($output);

    return $output;
  }

  /**
   * Get a options array to use with criteriaMet method.
   *
   * @return array
   *   Array of operators.
   */
  public function getCriteriaMetOperatorsOptions() {
    $operators = [
      self::BIGGER            => '>',
      self::BIGGER_OR_EQUALS  => '>=',
      self::SMALLER           => '<',
      self::SMALLER_OR_EQUALS => '<=',
      self::EQUALS            => '=',
      self::DIFFERENT         => '!=',
      self::IS_EMPTY          => $this->t('Data value is empty'),
      self::CONTAINS          => $this->t('Contains'),
      self::STARTS_WITH       => $this->t('Starts with'),
      self::ENDS_WITH         => $this->t('Ends with'),
    ];

    return $operators;
  }

  /**
   * Return the current Url.
   *
   * @return \Drupal\Core\Url|null
   *   The Url.
   */
  public function getCurrentUri() {
    // $current = $this->request->server->get('REQUEST_URI');.
    $current      = $_SERVER['REQUEST_URI'];
    $fake_request = Request::create($current);
    $url_object   = $this->container->get('path.validator')
      ->getUrlIfValid($fake_request->getRequestUri());
    if ($url_object) {
      return $url_object;
    }

    return NULL;
  }

  /**
   * Return all content entity types.
   *
   * @return array
   *   Array of entity types. [id => label]
   */
  public function getEntityTypes() {
    $output = [];

    $types = $this->entityTypeManager->getDefinitions();
    foreach ($types as $key => $type) {
      if ($type instanceof ContentEntityType) {
        $output[$key] = $type->getLabel();
      }
    }

    asort($output);

    return $output;
  }

  /**
   * Get the Business Rules keyValueExpirable collection.
   *
   * @param string $collection
   *   The keyvalue collection.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   *   The keyValueFactory.
   */
  public function getKeyValueExpirable($collection) {
    return $this->keyValueExpirable->get('business_rules.' . $collection);
  }

  /**
   * Return the previous Url.
   *
   * @return \Drupal\Core\Url|null
   *   The Url.
   */
  public function getPreviousUri() {
    try {
      $previousUrl  = $_SERVER['HTTP_REFERER'];
      $fake_request = Request::create($previousUrl);
      $url_object   = $this->container->get('path.validator')
        ->getUrlIfValid($fake_request->getRequestUri());
      if ($url_object) {
        return $url_object;
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e);
    }

    return NULL;
  }

  /**
   * Get all user roles.
   *
   * @return array
   *   Options array.
   */
  public function getUserRolesOptions() {
    $roles   = $this->entityTypeManager->getStorage('user_role')
      ->loadMultiple();
    $options = [];

    /**@var  \Drupal\user\Entity\Role $role */
    foreach ($roles as $role) {
      $options[$role->id()] = $role->label();
    }
    asort($options);

    return $options;
  }

  /**
   * Return a details box which the available variables for use on this context.
   *
   * @param \Drupal\business_rules\Entity\BusinessRulesItemBase $item
   *   The business Rule Item.
   * @param string $plugin_type
   *   The variable plugin type id.
   *
   * @return array
   *   The render array.
   */
  public function getVariablesDetailsBox(BusinessRulesItemBase $item, $plugin_type = '') {

    $target_entity_type  = $item->getTargetEntityType();
    $target_bundle       = $item->getTargetBundle();
    $variables           = Variable::loadMultiple();
    $available_variables = [];
    $details             = [];

    if (is_array($variables)) {
      /** @var \Drupal\business_rules\Entity\Variable $variable */
      foreach ($variables as $variable) {
        // Check targetBundle.
        if (((($variable->getTargetBundle() == $target_bundle || empty($target_bundle) || empty($variable->getTargetBundle()))
              // Check targetEntity.
              && ($variable->getTargetEntityType() == $target_entity_type || empty($target_entity_type) || empty($variable->getTargetEntityType())))
            // Check context dependency.
            || (!$variable->isContextDependent()))
          // Check plugin type.
          && ($plugin_type == '' || $plugin_type == $variable->getType())
          // Check if it's the variable being edited.
          && (($item instanceof Variable && $item->id() != $variable->id()) || !$item instanceof Variable)
        ) {
          $available_variables[] = $variable;
        }
      }
    }

    if (is_array($available_variables) && count($available_variables)) {
      $storage = $this->entityTypeManager->getStorage('business_rules_variable');
      $list    = new VariableListBuilder($variable->getEntityType(), $storage);

      $details = [
        '#type'        => 'details',
        '#title'       => $this->t('Available Variables for this context'),
        '#collapsed'   => TRUE,
        '#collapsable' => TRUE,
      ];

      $header           = $list->buildHeader();
      $new_header['id'] = $this->t('Variable');
      unset($header['id']);
      foreach ($header as $key => $item) {
        $new_header[$key] = $item;
      }
      $header = $new_header;

      $rows = [];
      foreach ($available_variables as $variable) {
        $row           = $list->buildRow($variable);
        $new_row['id'] = '{{' . $row['id']['data']['#markup'] . '}}';
        unset($row['id']);
        foreach ($row as $key => $item) {
          $new_row[$key] = $item;
        }

        // Give a chance to the variable plugin change the details about this
        // availability.
        $type             = $variable->getType();
        $variable_type    = $this->variableManager->getDefinition($type);
        $reflection       = new \ReflectionClass($variable_type['class']);
        $defined_variable = $reflection->newInstance($variable_type, $variable_type['id'], $variable_type);
        $defined_variable->changeDetails($variable, $new_row);

        $rows[] = $new_row;
      }

      $details['variables'] = [
        '#type'   => 'table',
        '#header' => $header,
        '#rows'   => $rows,
      ];
    }

    return $details;

  }

  /**
   * Get a variables options array.
   *
   * @param array $variable_types
   *   The variable type. Leave empty if you need all variables..
   * @param array $entity_type
   *   Variable entity type. Empty for all.
   * @param array $bundle
   *   Variable bundle. Empty for all.
   *
   * @return array
   *   Options array.
   */
  public function getVariablesOptions(array $variable_types = [], array $entity_type = [], array $bundle = []) {
    $options = [];

    $variables = Variable::loadMultiple();
    /** @var \Drupal\business_rules\Entity\Variable $variable */
    foreach ($variables as $variable) {
      if ((!count($variable_types) || in_array($variable->getType(), $variable_types))
        && (!count($entity_type) || in_array($variable->getTargetEntityType(), $entity_type))
        && (!count($bundle) || in_array($variable->getTargetBundle(), $bundle))
      ) {
        $options[$variable->id()] = $variable->label() . ' [' . $variable->id() . ']';
      }
    }
    asort($options);

    return $options;
  }

  /**
   * Get a list of views to display in a option box.
   *
   * @param string $views_display
   *   The views display plugin if you want to filter by display.
   *
   * @return array
   *   Options array.
   */
  public function getViewsOptions($views_display = NULL) {

    $views   = Views::getAllViews();
    $options = [];

    foreach ($views as $view) {
      $id              = $view->id();
      $big_description = strlen($view->get('description') > 100) ? '...' : '';
      foreach ($view->get('display') as $display) {
        if (empty($views_display) || $display['display_plugin'] == $views_display) {
          $options[$view->label() . ' : ' .
          substr($view->get('description'), 0, 100) .
          $big_description][$id . ':' . $display['id']] = $this->t('@view : @display_id : @display_title', [
            '@view'          => $view->label(),
            '@display_id'    => $display['id'],
            '@display_title' => $display['display_title'],
          ]);
        }
      }
    }
    ksort($options);

    return $options;
  }

  /**
   * Display the entity variable fields.
   *
   * @param \Drupal\business_rules\Entity\Variable $variable
   *   The variable entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   The AjaxResponse or the render array.
   */
  public function getVariableFieldsModalInfo(Variable $variable) {

    $fields      = $this->entityFieldManager->getFieldDefinitions($variable->getTargetEntityType(), $variable->getTargetBundle());
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    $header = [
      'variable' => $this->t('Variable'),
      'field'    => $this->t('Field'),
      'type'     => $this->t('Type'),
    ];

    $rows = [];
    foreach ($fields as $field_name => $field_storage) {
      $field_type = $field_storage->getType();
      $rows[]     = [
        'variable' => ['data' => ['#markup' => '{{' . $variable->id() . '->' . $field_name . '}}']],
        'field'    => ['data' => ['#markup' => $field_storage->getLabel()]],
        'type'     => ['data' => ['#markup' => $field_types[$field_type]['label']]],
      ];
    }

    $content['help'] = [
      '#type'   => 'markup',
      '#markup' => $this->t('To access a particular multi-value field such as target id, you can use <code>{{@variable_id[delta]}}</code> where "delta" is the delta value to get a one value or <code>{{@variable_id}}</code> to get an array of values.
        <br>To access a particular multi-value field label you can use <code>{{@variable_id[delta]->label}}</code> where "delta" is the delta value to get one label or <code>{{@variable_id->label}}</code> to get an array of labels.', [
          '@variable_id' => $variable->id(),
        ]),
    ];

    $content['variable_fields'] = [
      '#type'   => 'table',
      '#rows'   => $rows,
      '#header' => $header,
      '#sticky' => TRUE,
    ];

    return $content;
  }

  /**
   * Remove the item references after it's deletion.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event.
   */
  public function removeItemReferences(BusinessRulesEvent $event) {
    /** @var \Drupal\business_rules\Entity\BusinessRulesItemBase $item */
    $item       = $event->getSubject();
    $conditions = $this->getConditionsUsingItem($item);
    $actions    = $this->getActionsUsingItem($item);
    $rules      = $this->getBusinessRulesUsingThisItem($item);

    // Variable's references work in a different manner.
    if ($item instanceof Variable) {
      return;
    }

    // Remove item from conditions.
    /** @var \Drupal\business_rules\Entity\Condition $condition */
    foreach ($conditions as $condition) {
      $success_items = $condition->getSuccessItems();
      /** @var \Drupal\business_rules\BusinessRulesItemObject $success_item */
      foreach ($success_items as $success_item) {
        if ($success_item->getId() == $item->id()) {
          $condition->removeSuccessItem($success_item);
        }
      }

      $fail_items = $condition->getFailItems();
      /** @var \Drupal\business_rules\BusinessRulesItemObject $fail_item */
      foreach ($fail_items as $fail_item) {
        if ($fail_item->getId() == $item->id()) {
          $condition->removeFailItem($fail_item);
        }
      }

      $condition->save();
    }

    // Remove item from actions.
    /** @var \Drupal\business_rules\Entity\Action $action */
    foreach ($actions as $action) {
      $action_items = $action->getSettings('items');
      unset($action_items[$item->id()]);
      $action->setSetting('items', $action_items);

      $action->save();
    }

    // Remove item from business rules.
    /** @var \Drupal\business_rules\Entity\BusinessRule $rule */
    foreach ($rules as $rule) {
      $rule_items = $rule->getItems();
      /** @var \Drupal\business_rules\BusinessRulesItemObject $rule_item */
      foreach ($rule_items as $rule_item) {
        if ($rule_item->getId() == $item->id()) {
          $rule->removeItem($rule_item);
        }
      }

      $rule->save();
    }

  }

  /**
   * Get all conditions using the item.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The item to look for conditions using it.
   *
   * @return array
   *   Conditions using the item.
   */
  public function getConditionsUsingItem(ItemInterface $item) {
    $conditions = Condition::loadMultiple();
    $used_by    = [];

    /** @var \Drupal\business_rules\Entity\Condition $condition */
    if ($item instanceof Variable) {
      foreach ($conditions as $condition) {
        /** @var \Drupal\business_rules\VariablesSet $variables */
        $variables = $condition->getVariables();
        if ($variables->count()) {
          /** @var \Drupal\business_rules\VariableObject $variable */
          foreach ($variables->getVariables() as $variable) {
            if ($variable->getId() == $item->id()) {
              $used_by[$variable->getId()] = $condition;
            }
          }
        }
      }
    }
    else {
      foreach ($conditions as $key => $condition) {
        if (in_array($item->id(), array_keys($condition->getSuccessItems()))) {
          $used_by[$key] = $condition;
        }
      }

      foreach ($conditions as $key => $condition) {
        if (in_array($item->id(), array_keys($condition->getFailItems()))) {
          $used_by[$key] = $condition;
        }
      }
    }

    return $used_by;
  }

  /**
   * Get all actions using the item.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The item to look for actions using it.
   *
   * @return array
   *   Actions using the item.
   */
  public function getActionsUsingItem(ItemInterface $item) {

    $actions = Action::loadMultiple();
    $used_by = [];

    /** @var \Drupal\business_rules\Entity\Action $action */
    if ($item instanceof Variable) {
      foreach ($actions as $action) {
        /** @var \Drupal\business_rules\VariablesSet $variables */
        $variables = $action->getVariables();
        if ($variables->count()) {
          /** @var \Drupal\business_rules\VariableObject $variable */
          foreach ($variables->getVariables() as $variable) {
            if ($variable->getId() == $item->id()) {
              $used_by[$variable->getId()] = $action;
            }
          }
        }
      }
    }
    else {
      foreach ($actions as $key => $action) {
        if (($action->getSettings('items')) && in_array($item->id(), array_keys($action->getSettings('items')))) {
          $used_by[$key] = $action;
        }
      }
    }

    return $used_by;
  }

  /**
   * Get all Business Rules using the item.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The item to look for business rules using it.
   *
   * @return array
   *   Actions using the item.
   */
  public function getBusinessRulesUsingThisItem(ItemInterface $item) {
    $rules   = BusinessRule::loadMultiple();
    $used_by = [];

    /** @var \Drupal\business_rules\Entity\BusinessRule $rule */
    foreach ($rules as $rule) {
      if (in_array($item->id(), array_keys($rule->getItems()))) {
        $used_by[] = $rule;
      }
    }

    return $used_by;
  }

  /**
   * Return a details box which rules in where this item is being used.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The item to get the business rules using it.
   *
   * @return array
   *   The render array.
   */
  public function getUsedByBusinessRulesDetailsBox(ItemInterface $item) {

    $used_by = $this->getBusinessRulesUsingThisItem($item);
    $details = [];

    if (count($used_by)) {
      /** @var \Drupal\business_rules\Entity\BusinessRule $rule */
      $rule = $used_by[array_keys($used_by)[0]];

      $storage = $this->entityTypeManager->getStorage('business_rule');
      $list    = new BusinessRuleListBuilder($rule->getEntityType(), $storage);

      $details = [
        '#type'        => 'details',
        '#title'       => $this->t('Business Rules using this item'),
        '#collapsed'   => TRUE,
        '#collapsable' => TRUE,
      ];

      $header = $list->buildHeader();

      $rows = [];
      foreach ($used_by as $rule) {
        $rows[] = $list->buildRow($rule);
      }

      $details['used_by'] = [
        '#type'   => 'table',
        '#header' => $header,
        '#rows'   => $rows,
      ];
    }

    return $details;

  }

  /**
   * Return a details box which conditions using this item.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The item to get the conditions using it.
   *
   * @return array
   *   The render array.
   */
  public function getUsedByConditionsDetailsBox(ItemInterface $item) {

    $used_by = $this->getConditionsUsingItem($item);
    $details = [];

    if (count($used_by)) {
      /** @var \Drupal\business_rules\Entity\Condition $condition */
      $condition = $used_by[array_keys($used_by)[0]];
      $storage   = $this->entityTypeManager->getStorage('business_rules_condition');
      $list      = new ConditionListBuilder($condition->getEntityType(), $storage);

      $details = [
        '#type'        => 'details',
        '#title'       => $this->t('Conditions using this item'),
        '#collapsed'   => TRUE,
        '#collapsable' => TRUE,
      ];

      $header = $list->buildHeader();

      $rows = [];
      foreach ($used_by as $condition) {
        $rows[] = $list->buildRow($condition);
      }

      $details['used_by'] = [
        '#type'   => 'table',
        '#header' => $header,
        '#rows'   => $rows,
      ];
    }

    return $details;
  }

  /**
   * Return a details box which actions using this item.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The item to get the actions using it.
   *
   * @return array
   *   The render array.
   */
  public function getUsedByActionsDetailsBox(ItemInterface $item) {

    $used_by = $this->getActionsUsingItem($item);
    $details = [];

    /** @var \Drupal\business_rules\Entity\Condition $action */
    if (count($used_by)) {
      $action  = $used_by[array_keys($used_by)[0]];
      $storage = $this->entityTypeManager->getStorage('business_rules_action');
      $list    = new ActionListBuilder($action->getEntityType(), $storage);

      $details = [
        '#type'        => 'details',
        '#title'       => $this->t('Actions using this item'),
        '#collapsed'   => TRUE,
        '#collapsable' => TRUE,
      ];

      $header = $list->buildHeader();

      $rows = [];
      foreach ($used_by as $action) {
        $rows[] = $list->buildRow($action);
      }

      $details['used_by'] = [
        '#type'   => 'table',
        '#header' => $header,
        '#rows'   => $rows,
      ];
    }

    return $details;
  }

  /**
   * Convert the string in a safe lowercase format.
   *
   * @param string $string
   *   The string to convert to a safe lower value.
   *
   * @return string
   *   The safe lowercase string.
   */
  public function toSafeLowerString(&$string) {
    $string = trim(strtolower(htmlentities(strip_tags($string))));

    return $string;
  }

  /**
   * Performs Xss filter in all settings.
   *
   * @param array $array
   *   The settings array.
   */
  public function applyXssInArray(array &$array) {
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $this->applyXssInArray($value);
      }
      elseif (is_string($value)) {
        $array[$key] = Xss::filterAdmin($value);
      }
    }
  }

  /**
   * Return a the mapping fields for a given entity config schema.
   *
   * It's based on *.schema.yml file.
   *
   * @param string $schema_name
   *   The schema name.
   *
   * @return array
   *   Array with schema fields.
   */
  public function getFieldsSchema($schema_name) {
    $schema = \Drupal::service('config.typed')
      ->getDefinition($schema_name);
    $result = $this->getMappingsArray($schema);

    return $result;
  }

  /**
   * Helper function to return the fields schema.
   *
   * @param array $schema
   *   The schema array.
   *
   * @return array
   *   The items that belongs to array mapping key.
   */
  private function getMappingsArray(array $schema) {
    $result = [];

    foreach ($schema as $key => $value) {
      if ($key == 'mapping') {
        foreach ($value as $mk => $mv) {
          $result[] = $mk;
          if (isset($value['mapping'])) {
            $result += $this->getMappingsArray($mv);
          }
          // Elseif (is_array($))
        }
      }
    }

    return $result;
  }

}
