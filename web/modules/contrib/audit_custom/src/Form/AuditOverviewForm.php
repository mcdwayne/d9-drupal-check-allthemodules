<?php

namespace Drupal\audit\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;

/**
 *
 */
class AuditOverviewForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $id = 0) {
    // During the initial form build, add this form object to the form state and
    // allow for initial preparation before form building and processing.
    if (!$form_state->has('entity_form_initialized')) {
      $this->init($form_state);
    }

    // Ensure that edit forms have the correct cacheability metadata so they can
    // be cached.
    if (!$this->entity->isNew()) {
      \Drupal::service('renderer')->addCacheableDependency($form, $this->entity);
    }

    // Retrieve the form array using the possibly updated entity in form state.
    $form = $this->form($form, $form_state, $entity_type, $id);

    // Retrieve and add the form actions array.
    $actions = $this->actionsElement($form, $form_state);
    if (!empty($actions)) {
      $form['actions'] = $actions;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, $entity_type = NULL, $id = 0) {
    $form = parent::form($form, $form_state);

    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($id);

    $aud_entities = [];
    $aid_entities = $entity->get('aids');
    foreach ($aid_entities as $aid_entity) {
      $aud_entities[$aid_entity->entity->id()] = $aid_entity->entity;
    }

    $current_user = \Drupal::currentUser()->id();
    $current_entity_audit = '';
    $aud = $descriptions = [];
    foreach ($aud_entities as $row) {
      $description_i = 1;
      foreach ($row->get('description') as $a) {
        if ($a->value == '继续添加') {
          continue;
        }
        $descriptions[$row->id()][$description_i] = SafeMarkup::format($a->value, []);
        $description_i++;
      }

      $description_first = $description_other = '';
      if (isset($descriptions[$row->id()])) {
        $description_first = $descriptions[$row->id()][1];
        unset($descriptions[$row->id()][1]);

        $description_other = $descriptions[$row->id()];
      }
      $audit_user = $row->get('auid')->entity;
      $file_entity = $audit_user->get('user_picture');
      $file_uri = empty($file_entity->entity) ? '' : $file_entity->entity->getFileUri();

      $aud[$row->id()] = [
        'id' => $row->id(),
        'user' => [
          'name' => $audit_user->get('realname')->value,
          'avatar' => empty($file_uri) ? '' : file_create_url($file_uri),
        ],
        'isself' => $current_user == $row->get('auid')->target_id ? 1 : 0,
        'created' => \Drupal::service('date.formatter')->format($row->get('changed')->value, 'xyfulldate'),
        'isaudit' => $row->get('isaudit')->value,
        'status' => $row->get('status')->value,
        'description_first' => [
          'first' => $description_first,
          'other' => $description_other,
        ],
      ];
    }
    $form['audits'] = [
      '#markup' => $aud,
    ];
    $form['param'] = [
      '#type' => 'value',
      '#value' => $aud_entities,
    ];
    $form['module'] = [
      '#type' => 'value',
      '#value' => [
        'module' => $entity_type,
        'id' => $id,
      ],
    ];

    $form['spec_entity'] = [
      '#type' => 'value',
      '#value' => $entity,
    ];
    $form['#attached']['library'] = ['audit/audit_overview'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $entity = $form['spec_entity']['#value'];

    if ($entity->get('uid')->target_id != \Drupal::currentUser()->id()) {
      $actions['audit_append_submit'] = [
        '#type' => 'submit',
        '#value' => '追加评论',
        '#id' => 'check_audit_append',
        '#submit' => ['::save'],
        '#attributes' => [
          'class' => ['btn-primary'],
        ],
      ];
    }
    $audit_status = 0;
    // 审批人数组.
    $aids = [];
    // 当前用户如果已经审核，则不再显示‘同意或拒绝’按钮.
    foreach ($entity->get('aids') as $row) {
      if ($row->entity->get('auid')->target_id == \Drupal::currentUser()->id()) {
        $audit_status = $row->entity->get('isaudit')->value;
      }
      $aids[] = $row->entity->get('auid')->target_id;
    }
    // 如果当前实体的状态为4，已拒绝。则不再提供其他人处理其他状态
    // if ($entity->get('status')->value != 4 && $entity->get('uid')->target_id != \Drupal::currentUser()->id() && !$audit_status) {
    // 屏蔽创建者无法审批功能.
    if ($entity->get('status')->value != 4 && !$audit_status && in_array(\Drupal::currentUser()->id(), $aids)) {
      $actions['audit_accept_submit'] = [
        '#type' => 'submit',
        '#value' => '同意',
        '#submit' => ['::auditAcceptSubmitForm'],
        '#id' => 'check_audit_accept',
        '#attributes' => [
          'class' => ['btn-success'],
        ],
      ];

      $actions['audit_reject_submit'] = [
        '#type' => 'submit',
        '#value' => '拒绝',
        '#submit' => ['::auditRejectSubmitForm'],
        '#id' => 'check_audit_reject',
        '#attributes' => [
          'class' => ['btn-danger'],
        ],
      ];
    }
    /**
     * @description 单据模型处于审批中或被拒时，可对单据进行撤销，并重新发起审批.
     */
    if (in_array($entity->get('status')->value, [2, 4]) && $entity->get('uid')->target_id == \Drupal::currentUser()->id()) {
      $actions['cancel_audit_submit'] = [
        '#type' => 'submit',
        '#value' => '撤销审批',
        '#submit' => ['::auditCancelSubmitForm'],
        '#id' => 'check_audit_cancel',
        '#attributes' => [
          'class' => ['btn-danger'],
        ],
      ];
    }
    unset($actions['submit']);
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    // 当前传入的audit实体，
    // 1. 需要更新isaudit状态
    // 2. 保存审批意见
    // [].
    $descriptions = $form_state->getValue('description');

    array_pop($descriptions);
    // 判断当前用户在当前模型实体内的审批用户是否相同，不同则不进行操作，相同时，对相应审核用户的描述进行更新。
    // 只对描述的保存有效。.
    $param = $form_state->getValue('param');
    $module = $form_state->getValue('module');
    foreach ($param as $p) {
      $description_i = 1;
      $descriptions_old = [];
      foreach ($p->get('description') as $a) {
        $descriptions_old[$description_i] = SafeMarkup::format($a->value, []);
        $description_i++;
      }

      if ($p->get('auid')->target_id == \Drupal::currentUser()->id()) {
        $p->set('description', array_merge($descriptions, $descriptions_old))->save();
      }
    }
    $form_state->setRedirectUrl(new Url("entity." . $module["module"] . ".detail_form", [$module['module'] => $module['id']]));
    drupal_set_message('成功追加评论!');
  }

  /**
   * @description 处理审批同意
   */
  public function auditAcceptSubmitForm(array $form, FormStateInterface $form_state) {
    // 当前传入的audit实体，
    // 1. 需要更新isaudit状态
    // 2. 保存审批意见
    // [].
    $descriptions = $form_state->getValue('description');

    array_pop($descriptions);
    // 判断当前用户在当前模型实体内的审批用户是否相同，不同则不进行操作，相同时，对相应审核用户的描述进行更新。
    // 只对描述的保存有效。.
    $param = $form_state->getValue('param');
    $module = $form_state->getValue('module');
    foreach ($param as $p) {
      $description_i = 1;
      $descriptions_old = [];
      foreach ($p->get('description') as $a) {
        $descriptions_old[$description_i] = SafeMarkup::format($a->value, []);
        $description_i++;
      }

      if ($p->get('auid')->target_id == \Drupal::currentUser()->id()) {
        $p->set('description', array_merge($descriptions, $descriptions_old))->save();
        $p_param = [
        // Audit 已审核  该值只有0，1.
          'isaudit' => 1,
        // Audit 已同意.
          'status' => 3,
        ];
        $status = \Drupal::service('audit.auditservice')->setAuditStatus($p, $p_param, $module);
        \Drupal::service('audit_locale.audit_localeservice')->sendMailtoAuditUid($form_state->getValue('spec_entity'));
      }
      else {
        error_log(print_r('当前用户不在该付款单审批人列表里面', 1));
      }
    }
    $form_state->setRedirectUrl(new Url("entity." . $module["module"] . ".detail_form", [$module['module'] => $module['id']]));
    drupal_set_message('该审批单已同意');
  }

  /**
   * @description 处理审批拒绝时
   */
  public function auditRejectSubmitForm(array $form, FormStateInterface $form_state) {
    // 当前传入的audit实体，
    // 1. 需要更新isaudit状态
    // 2. 保存审批意见
    // [].
    $descriptions = $form_state->getValue('description');

    array_pop($descriptions);
    // 判断当前用户在当前模型实体内的审批用户是否相同，不同则不进行操作，相同时，对相应审核用户的描述进行更新。
    // 只对描述的保存有效。.
    $param = $form_state->getValue('param');
    $module = $form_state->getValue('module');
    foreach ($param as $p) {
      $description_i = 1;
      $descriptions_old = [];
      foreach ($p->get('description') as $a) {
        $descriptions_old[$description_i] = SafeMarkup::format($a->value, []);
        $description_i++;
      }

      if ($p->get('auid')->target_id == \Drupal::currentUser()->id()) {
        $p->set('description', array_merge($descriptions, $descriptions_old))->save();
        $p_param = [
          'isaudit' => 1,
          'status' => 2,
        ];
        \Drupal::service('audit.auditservice')->setAuditStatus($p, $p_param, $module);
        \Drupal::service('audit_locale.audit_localeservice')->sendMailtoAuditUid($form_state->getValue('spec_entity'));
      }
    }
    $form_state->setRedirectUrl(new Url("entity." . $module["module"] . ".detail_form", [$module['module'] => $module['id']]));
    drupal_set_message('成功拒绝该审批单!');
  }

  /**
   * @description 处理取消审批时
   */
  public function auditCancelSubmitForm(array $form, FormStateInterface $form_state) {
    // 当前传入的audit实体，
    // 1. 需要更新isaudit状态
    // 2. 保存审批意见
    // [].
    $descriptions = $form_state->getValue('description');

    array_pop($descriptions);
    // 判断当前用户在当前模型实体内的审批用户是否相同，不同则不进行操作，相同时，对相应审核用户的描述进行更新。
    // 只对描述的保存有效。.
    $param = $form_state->getValue('param');
    $module = $form_state->getValue('module');
    $entity = \Drupal::entityTypeManager()->getStorage($module['module'])->load($module['id']);
    /**
     * @description 取消审批包含以下相关动作。
     * 1. 删除该需求单的相关审批人。
     * 2. 还原需求单或相关单的状态，还原为未审批.
     */
    /**
     * 下面这个是需求单的取消审批动作
     */
    $entity->set('aids', [])
      ->set('status', 0)
      ->set('audit', 0)
      ->save();
    $form_state->setRedirectUrl(new Url("entity." . $module["module"] . ".detail_form", [$module['module'] => $module['id']]));
    drupal_set_message('成功取消审批!');

  }

}
