<?php

namespace Drupal\audit;

use Drupal\Core\Database\Connection;

/**
 *
 */
class AuditService {

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
   * Audit entity table save.
   *
   * @param $fields
   *   [] module全部相同，module_id全部相同
   */
  public function save($entity, $update = TRUE, $audit = NULL) {
    $audit = '';
    if ($update) {
      // @todo 待处理
    }
    else {
      if (!empty($audit)) {
        $audit = \Drupal::entityTypeManager()->getStorage('audit')->create([
          'role' => '',
          'auid' => $audit_locale->get('aid')->target_id,
          'weight' => $audit_locale->get('weight')->value,
          'uid' => \Drupal::currentUser()->id(),
          'status' => 0,
          'isaudit' => 0,
        ]);
        $audit->save();
      }
    }
    return $audit;
  }

  /**
   * @description 设置当前审批人的审批状态.
   * @param  $audit
   * @param  $module
   * @code
   *  $module = [
   *    'module' => 'requirement',
   *    'id' => 1,
   *  ];
   * @endcode
   */
  public function setAuditStatus($audit, $param = [], $module = []) {
    if (empty($param) || empty($module)) {
      return -1;
    }
    else {
      $audit->set('isaudit', $param['isaudit'])
        ->set('status', $param['status'])
        ->save();

      // 当审批人拒绝时.
      if ($param['status'] == 2) {
        error_log(print_r('audit status set refuse', 1));
        $this->setAuditFailure($module, $param['status']);
      }

      // 当该模型的审批实体不存在状态为3，已同意的其他状态时，更新该模型的审批状态.
      if (!$this->checkAllAudit($module)) {
        $this->setAuditAllSuccess($module);
      }
      return 1;
    }
  }

  /**
   * @description 设置相关模块实体的失败状态
   */
  public function setAuditFailure($module = []) {
    $entity = \Drupal::entityTypeManager()->getStorage($module['module'])->load($module['id']);

    // 审批人直接拒绝
    // 处理对应实体的工单状态.
    switch ($module['module']) {
      case 'requirement':
        // 工单的审批状态为2， 已拒绝,.
        $entity->set('audit', 2)
        // 需求工单的状态为4 拒绝， 当某个审批人拒绝时.
          ->set('status', 4)
          ->save();
        // 保存配件的状态.
        \Drupal::service('part.partservice')->updatePartStatus($entity);
        break;

      case 'purchase':
        // 工单的审批状态为2， 已拒绝,.
        $entity->set('audit', 2)
        // 采购工单的状态为6 拒绝， 当某个审批人拒绝时.
          ->set('status', 6)
          ->save();
        // 保存配件的状态.
        \Drupal::service('part.partservice')->updatePartStatus($entity);
        break;

      case 'paypre':
        // 工单的审批状态为2， 已拒绝,.
        $entity->set('audit', 2)
        // 付款工单的状态为4拒绝,当某个审批人拒绝时.
          ->set('status', 4)
          ->save();
        // 保存配件的状态,.
        // @todo 当付款单审批被拒绝后，
        //   处理对应的配件，需求，及采购单相关状态。.
        \Drupal::service('purchase.purchaseservice')->updatePurchaseStatus4PaypreOnRejectAction($entity);
        break;

      case 'paypro':
        // 工单的审批状态为2， 已拒绝,.
        $entity->set('audit', 2)
        // 支付工单的状态为4拒绝,当某个审批人拒绝时.
          ->set('status', 4)
          ->save();
        // 1. 拒绝付款单.
        \Drupal::service('paypre.paypreservice')->updatePaypreStatus4PayproOnRejectAction($entity);
        // 2. 拒绝采购单
        // 已处理
        // 3. 拒绝配件
        // 已处理.
        break;
    }
  }

  /**
   * @description 这个函数只在Audit所有实体都已经成功审核时执行.
   */
  public function setAuditAllSuccess($module = []) {
    $entity = \Drupal::entityTypeManager()->getStorage($module['module'])->load($module['id']);
    switch ($module['module']) {
      case 'requirement':
        // 需求工单的状态.
        $entity->set('status', 5)
        // 需求工单的审批状态.
          ->set('audit', 3)
        // 当全部审批都通过后，需求单状态更改为已同意。.
          ->save();
        // 保存配件的状态.
        \Drupal::service('part.partservice')->updatePartStatus($entity);
        break;

      case 'purchase':
        // 采购工单的状态.
        $entity->set('status', 5)
        // 采购工单的审批状态.
          ->set('audit', 3)
        // 当全部审批都通过后，采购单状态更改为已同意。.
          ->save();
        // 保存配件的状态.
        \Drupal::service('part.partservice')->updatePartStatus($entity);
        break;

      case 'paypre':
        // 付款单的状态.
        $entity->set('status', 5)
        // 付款工单的审批状态.
          ->set('audit', 3)
        // 当全部通过审核后，更新付款单的工单状态.
          ->save();
        // 并且修改所包含的采购单的状态为待付款.
        \Drupal::service('purchase.purchaseservice')->updatePurchaseStatus4Paypre($entity);
        break;

      case 'paypro':
        // 支付单的状态.
        $entity->set('status', 10)
        // 支付工单的审批状态.
          ->set('audit', 3)
        // 当全部通过审核后，更新支付单的工单状态.
          ->save();
        // 并且修改所包含的采购单的状态为待支付.
        \Drupal::service('paypre.paypreservice')->updatePaypreStatus4Paypro($entity);
        break;
    }
  }

  /**
   * @descripiton 检测审批人是否全部审批完成.
   */
  public function checkAllAudit($module) {
    $aid_status = [];

    if (empty($module)) {
      error_log(print_r('检测审批人是否全部审批完成-否', 1));
      return 1;
    }

    $entity = \Drupal::entityTypeManager()->getStorage($module['module'])->load($module['id']);
    $aids = $entity->get('aids');
    foreach ($aids as $aid) {
      error_log(print_r('audit status 3 check', 1));
      if ($aid->entity->get('status')->value != 3) {
        $aid_status[] = 1;
      }
    }

    return count($aid_status);
  }

}
