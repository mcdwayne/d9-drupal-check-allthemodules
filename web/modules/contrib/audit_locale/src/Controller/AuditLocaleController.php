<?php

namespace Drupal\audit_locale\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class AuditLocaleController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('date.formatter'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a Controller object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, DateFormatter $date_formatter, FormBuilderInterface $form_builder) {
    $this->database      = $database;
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder   = $form_builder;
  }

  /**
   * @description 获取审批实体的审批流程
   */
  public function getAuditWorkflow($module, $id = 0) {
    $build = [];

    if (!empty($module)) {
      $has_aids = \Drupal::service('audit_locale.audit_localeservice')->getModuleAuditLocale($module, $id);
      $audit_users = \Drupal::service('audit_locale.audit_localeservice')->getAuditUsers($has_aids);
    }
    if ($id != 0) {
      $audit_status = '该流程为当前单的审批流程';
    }
    else {
      $audit_status = '该流程为当前模型的通用审批流程';
    }
    $build['part'] = [
      '#theme' => 'audit_locale_rule_overview',
      '#rules' => [
        'audit_user' => $audit_users,
        'audit_status' => $audit_status,
      ],
      '#attached' => [
        'library' => ['audit_locale/audit_locale_overview_form'],
        'drupalSettings' => [
          'audit_locale' => [
            'id' => $id,
            'module' => $module,
          ],
        ],
      ],
    ];
    return $build;
  }

  /**
   * @description 获取审批实体的审批流程
   */
  public function setAuditWorkflow($module, $id = 0) {
    $build = [];

    if (!empty($module)) {
      $has_aids = \Drupal::service('audit_locale.audit_localeservice')->getModuleAuditLocale($module, $id);
      $audit_users = \Drupal::service('audit_locale.audit_localeservice')->getAuditUsers($has_aids);
    }

    $account = \Drupal::currentUser();
    $user = user_load($account->id());

    $tid = $user->get('company')->value;

    $tree = \Drupal::service('audit_locale.audit_localeservice')->getCompanyArchTree($tid);

    $build['part'] = [
      '#theme' => 'audit_locale_rule_edit',
      '#rules' => [
        'audit_user' => $audit_users,
        'treedata' => $tree,
      ],
      '#attached' => [
        'library' => ['audit_locale/audit_locale_rule_edit_form'],
        'drupalSettings' => [
          'audit_locale' => [
            'id' => $id,
            'module' => $module,
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * 获取企业架构列表.
   */
  public function setAuditLocalAutocomplete(Request $request, $module, $id = 0) {
    $response = ['创建失败'];
    if (empty($module)) {
      return new JsonResponse($response);
    }

    $items = $request->request->all();
    $weight = 0;
    $uids = [];
    foreach ($items['id'] as $row) {
      $fields[] = [
        'aid' => substr($row, strpos($row, '-') + 1),
        'weight' => $weight,
        'module' => $module,
        'module_id' => $id,
        'role' => NULL,
      ];
      $weight++;
    }
    \Drupal::service('audit_locale.audit_localeservice')->save(NULL, FALSE, [], $fields);
    return new JsonResponse(['保存成功']);
  }

  /**
   * Ajax get audit_locales.
   *
   * @param $input
   *
   * @code
   *  $input = [
   *    '_search' => false,
   *    'nd' => 1496842536048,
   *    'rows' => 50,
   *    'page' => 1,
   *    'sidx' => "name",
   *    'sord' => "asc",
   *    'filters' => "",
   *  ];
   * @endcode
   * @see audit_locale.pool.collection
   */
  public function getAuditLocalesAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxCollection($request, 'audit_locale');
    $i = 0;
    foreach ($entities as $entity) {
      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'module' => \Drupal::entityManager()->getStorage($entity->get('module')->value)->getEntityType()->getLabel()->getUntranslatedString(),
        'module_id' => $entity->get('module_id')->value == 0 ? '通用流程' : '特批流程:' . $entity->get('module_id')->value,
        'role' => '未指定',
        'weight' => $entity->get('weight')->value,
        'aid' => isset($entity->get('aid')->entity->get('realname')->value) ? $entity->get('aid')->entity->get('realname')->value : $entity->get('aid')->entity->label(),
        'uid' => isset($entity->get('uid')->entity->get('realname')->value) ? $entity->get('uid')->entity->get('realname')->value : $entity->get('uid')->entity->label(),
        'created' => isset($entity->get('created')->value) ? \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short') : '-',
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   *
   */
  private function getAjaxCollection(Request $request, $entity_type, $status = 1) {
    $input = $request->query->all();

    $page = $input['page'];
    $limit = $input['rows'];
    $sidx = $input['sidx'];
    $sord = $input['sord'];

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();

    switch ($entity_type) {
      case 'part':
        /**
         * @description 正常的需求池物品.
         */
        if ($status) {
          $storage_query->condition('save_status', 1);
          $storage_query->condition('rno', 0, '<>');
          $storage_query->condition('cno', 0);
          $storage_query->condition('fno', 0);
          $storage_query->condition('pno', 0);
        }
        else {
          /**
           * @description 非正常的需求池物品.
           */
          $storage_query->condition('save_status', 0);
        }
        break;

      case 'audit_locale':
        $storage_query->condition('uid', 0, '<>');
        $storage_query->condition('deleted', 1);
      default:
        $storage_query->condition('id', 0, '<>');
    }
    // @todo 添加采购审批中，采购中，待处理等条件查询.

    // Count.
    $count_result = $storage_query->execute();

    $count = count($count_result);
    if ($page == 0 || $page == 1) {
      $page = 1;
    }

    if ($count > 0) {
      $total_pages = ceil($count / $limit);
    }
    else {
      $total_pages = 0;
    }
    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $start = $limit * $page - $limit;

    $storage_query->sort($sidx, $sord);
    $storage_query->range($start, $limit);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];
  }

}
