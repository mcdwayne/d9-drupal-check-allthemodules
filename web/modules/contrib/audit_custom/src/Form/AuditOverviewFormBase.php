<?php

namespace Drupal\audit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * 工单服务器上下架类详情表单.
 */
class AuditOverviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'audit_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $id = NULL) {

    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($id);

    $aud_entities = [];
    $aid_entities = $entity->get('aids');
    foreach ($aid_entities as $aid_entity) {
      $aud_entities[$aid_entity->entity->id()] = $aid_entity->entity;
    }

    $current_user = \Drupal::currentUser()->id();
    foreach ($aud_entities as $row) {
      $aud[$row->id()] = [
        'id' => $row->id(),
        'user' => [
          'name' => 'admin',
          'avatar' => '',
        ],
        'isself' => $current_user == $row->get('auid')->target_id ? 1 : 0,
        'created' => $row->get('created')->value,
        'isaudit' => $row->get('isaudit')->value,
        'description' => $row->get('description')->value,
      ];
      if ($row->get('isaudit')->value == 0) {
        break;
      }
    }
    if ($current_user == $row->get('auid')->target_id) {
      $form['description'] = [
        '#type' => 'text_textfield',
        '#title' => 'Description',
        '#default_value' => $row->get('description'),
        '#format' => $row->format,
      ];
    }
    else {
      $form['description2'] = [
        '#type' => 'text_textfield',
        '#title' => '追加评论',
        '#default_value' => $row->get('description'),
        '#format' => $row->format,
      ];
    }
    $form['audits'] = [
      '#markup' => $aud,
    ];
    $form['#theme'] = 'audit_overview_form';
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   *
   * @todo 需要添加额外的权限验证，以保存当前审批用户的意见。
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
