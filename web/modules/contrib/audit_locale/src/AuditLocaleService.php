<?php

namespace Drupal\audit_locale;

use Drupal\Core\Database\Connection;

/**
 *
 */
class AuditLocaleService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   *
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Audit_locale entity table save.
   *
   * @param $fields
   *   [] module全部相同，module_id全部相同
   */
  public function save($entity, $update = TRUE, $ids = [], $fields = []) {
    $first = current($fields);
    $aids = $this->getModuleAuditLocale($first['module'], $first['module_id']);

    if (count($aids) == 0) {
      foreach ($fields as $field) {
        $entity = \Drupal::entityTypeManager()->getStorage('audit_locale')->create($field);
        $entity->save();
      }
    }
    // 先删除，后创建.
    else {
      $audit_locales = \Drupal::entityTypeManager()->getStorage('audit_locale')->loadMultiple($aids);
      foreach ($audit_locales as $audit_locale) {
        // $audit_locale->delete();
        $audit_locale->set('deleted', 0)
          ->save();
      }
      foreach ($fields as $field) {
        $entity = \Drupal::entityTypeManager()->getStorage('audit_locale')->create($field);
        $entity->save();
      }
    }

  }

  /**
   * 获取audit_locale指定的记录.
   *
   * @param string $module
   * @param int $id
   *
   * @return $ids []
   */
  public function getModuleAuditLocale($module, $id = 0, $deleted = 1) {
    $storage = \Drupal::entityManager()->getStorage("audit_locale");
    $storage_query = $storage->getQuery();
    $storage_query->condition('module', $module);
    $storage_query->condition('module_id', $id);
    if ($deleted) {
      $storage_query->condition('deleted', 1);
    }
    $storage_query->sort('weight', 'ASC');
    $ids = $storage_query->execute();

    return $ids;
  }

  /**
   * @description 获取该审批流包含的用户信息.
   * @param  $entity_audit_locales
   *
   *   tips:
   *   1. 获取审批配置实体
   *   2. 获取用户的信息- 头像，部门，用户名，真实名.
   */
  public function getAuditUsers($aids) {
    $audit_locales = \Drupal::entityTypeManager()->getStorage('audit_locale')->loadMultiple($aids);
    $users = [];
    foreach ($audit_locales as $audit_locale) {
      $user = user_load($audit_locale->get('aid')->target_id);
      $depart = empty($user->get('depart')->value) ? 0 : $user->get('depart')->value;

      $file_entity = $user->get('user_picture');
      $file_uri = empty($file_entity->entity) ? '' : $file_entity->entity->getFileUri();

      $users[] = [
        'data_id' => $depart . '-' . $audit_locale->get('aid')->target_id,
        'username' => $user->label(),
        'realname' => $user->get('realname')->value,
        'avatar' => empty($file_uri) ? '' : file_create_url($file_uri),
      ];
    }

    return $users;
  }

  /**
   * @description 根据用户部门id查找所属全部用户
   * @param $depart_ids
   *   [] 所有父级部门
   * @param  $depart
   * @return
   */
  public function getUsersByDepartmentId($depart_ids, $depart) {
    $storage = \Drupal::entityManager()->getStorage('user');

    $storage_query = $storage->getQuery();
    $storage_query->condition('depart', $depart);
    $ids = $storage_query->execute();
    $entities = $storage->loadMultiple($ids);

    $account_entities = [];
    if (!empty($depart_ids)) {
      $account_query = $storage->getQuery();
      $account_query->condition('depart', $depart_ids, 'IN');
      $ids_account = $account_query->execute();
      $account_entities = $storage->loadMultiple($ids_account);
    }

    $users = [];
    foreach ($entities as $entity) {
      $file_entity = $entity->get('user_picture');
      $file_uri = empty($file_entity->entity) ? '' : $file_entity->entity->getFileUri();
      $users[] = [
        'uid' => $entity->id(),
        'username' => $entity->label(),
        'realname' => $entity->get('realname')->value,
        'avatar' => empty($file_uri) ? '' : file_create_url($file_uri),
      ];
    }
    return [count($account_entities), $users];
  }

  /**
   * @param array $data
   *
   * @param int $pid
   *
   * @code
   *
   * @endcode
   */
  public function getTree($data, $pid = 0) {
    $tree = [];
    foreach ($data as $k => $v) {
      if ($v['pid'] == $pid) {
        $v['pid'] = $this->getTree($data, $v['id']);
        $tree[] = $v;
      }
    }
    return $tree;
  }

  /**
   *
   */
  public function getCompanyArchTree($company_id = 0) {
    if ($company_id == 0) {
      return;
    }

    // 获取当前用户的所属公司名.
    $pm = taxonomy_term_load($company_id);
    $entity_storage = \Drupal::service('entity.manager')->getStorage('taxonomy_term', 't');
    $childs = $entity_storage->loadTree('enterprises', $company_id, NULL, TRUE);

    $departs = [];
    $terms = array_merge($childs, [$pm]);

    $data = [];
    foreach ($terms as $depart) {
      $parent = current($entity_storage->loadParents($depart->id()));
      // 解决当前部门及子部门的总人数
      // 1. 获取所有子级部门
      // 2. 在这些子级部门中查找所有用户的人员总数.
      $depart_childs = $entity_storage->loadTree('enterprises', $depart->id(), NULL, TRUE);
      $depart_ids = [];
      if (!empty($depart_childs)) {
        foreach ($depart_childs as $row) {
          $depart_ids[] = $row->id();
        }
      }
      else {
        $depart_ids[] = $depart->id();
      }
      list($account, $users) = \Drupal::service('audit_locale.audit_localeservice')->getUsersByDepartmentId($depart_ids, $depart->id());
      $data[$depart->id()] = [
        'users' => $users,
        'name' => $depart->label(),
        'id' => $depart->id(),
        'pid' => $parent == FALSE ? 0 : $parent->id(),
        'count' => $account,
      ];
    }

    $tree = $this->getTree($data, 0);

    return $tree;

  }

  /**
   * @description 根据传入的$entity
   */
  public function setAudits4Module($entity, $status = 1) {
    $aids = [];

    $aids = $this->getModuleAuditLocale($entity->getEntityTypeId(), $entity->id());
    if (empty($aids)) {
      $aids = $this->getModuleAuditLocale($entity->getEntityTypeId());
    }
    // 发起审批后不再支持重复发起审批
    // status:0 为新单，支持审批
    // if ($entity->get('status')->value != 0) {.
    if ($entity->get('status')->value == 0 && !empty($aids)) {
      $audit_locales = \Drupal::entityTypeManager()->getStorage('audit_locale')->loadMultiple($aids);
      $audit_ids = [];
      foreach ($audit_locales as $audit_locale) {
        // 先保存audit实体，并取出audit的id
        // $audit = \Drupal::service('audit.auditservice')->save($audit_locale);
        $audit = \Drupal::entityTypeManager()->getStorage('audit')->create([
          // @todo 待后期添加角色组审批时，再处理此字段
          'role' => '',
          'auid' => $audit_locale->get('aid')->target_id,
          'weight' => $audit_locale->get('weight')->value,
          'uid' => \Drupal::currentUser()->id(),
          // 发起审批后status=1,处于待审批状态.
          'status' => 1,
          // 审批用户审批后，该状态将更新为1.
          'isaudit' => 0,
        ]);
        $audit->save();
        $audit_ids[] = $audit->id();
        $this->sendMailtoAuditUid($entity, $audit);
      }
      // 当前为requirements
      // 当前为purchase
      // 当前为paypre.
      $entity->set('aids', $audit_ids)
        ->save();
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   *
   */
  public function getAuditLocaleWorkflowStatus($entity) {
    if ($entity->get('status')->value != 0) {
      return [];
    }
    $module = $entity->getEntityTypeId();
    $id = $entity->id();
    $has_aids = \Drupal::service('audit_locale.audit_localeservice')->getModuleAuditLocale($module, $id);
    $audit_status = '该流程为当前单的审批流程';
    if (empty($has_aids)) {
      $has_aids = \Drupal::service('audit_locale.audit_localeservice')->getModuleAuditLocale($module, 0);
      $audit_status = '该流程为当前模型的通用审批流程';
    }
    $audit_users = \Drupal::service('audit_locale.audit_localeservice')->getAuditUsers($has_aids);
    $build = [
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
   * @description 给每个审批人发送待审批邮件信息。
   * @param $entity
   * @param $audit
   * @param $type
   *   0: 发起审批时，多个审批人的邮件发送
   *   1: 审批同意或拒绝时，给个人发送邮件
   */
  public function sendMailtoAuditUid($entity, $audit = NULL) {

    // 通用版邮件发送.
    if (!empty($audit)) {
      $type = \Drupal::entityTypeManager()->getDefinition($entity->getEntityTypeId())->getLabel()->render();
      $no = $entity->get('no')->value;
      $audit_user = $audit->get('uid')->entity;

      $params['subject'] = t('审批通知:' . $type . '编号: ' . $no . '待审批');
      // @todo 审批状态待添加

      $body_text = t('审批通知:' . $type . '编号: ' . $no . '待审批');
      $params['body'] = [$body_text];

      if (!empty($audit_user->get('mail')->value)) {
        // 临时用smtp的配置.
        \Drupal::service('plugin.manager.mail')->mail('smtp', 'smtp-test', $audit_user->getEmail(), $audit_user->getPreferredLangcode(), $params);
        // @todo 给80285394@qq.com
        // 抄送邮件
      }
    }
    else {
      // 审批同意或拒绝时发送此邮件.
      $type = \Drupal::entityTypeManager()->getDefinition($entity->getEntityTypeId())->getLabel()->render();
      $no = $entity->get('no')->value;

      $user = $entity->get('uid')->entity;

      $params['subject'] = t('审批通知:' . $type . '编号: ' . $no . '已审批');
      // @todo 审批状态待添加

      $body_text = t('审批状态: - 待添加@todo');
      $params['body'] = [$body_text];
      if (!empty($user->get('mail')->value)) {
        // 临时用smtp的配置.
        \Drupal::service('plugin.manager.mail')->mail('smtp', 'smtp-test', $user->getEmail(), $user->getPreferredLangcode(), $params);
        // @todo 给80285394@qq.com
        // 抄送邮件
      }
    }
  }

}
