<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\FindUserForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class FindUserForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_admin_find_user';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    _mm_ui_userlist_setup([0 => ''], $form, 'userlist', $this->t("User's name:"), TRUE, '');
    $form['userlist-choose']['#title'] = '';
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('View/edit user'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = mm_ui_mmlist_key0($form_state->getValue('userlist'));

    if (isset($uid)) {
      // @FIXME: test to make sure this works
      $form_state->setRedirect('entity.user.edit_form', array('user' => $uid), array('query' => array('destination' => 'admin/people/by-uid')));
    }
  }

}
