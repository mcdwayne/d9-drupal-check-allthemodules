<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\ListUsersForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\monster_menus\Constants;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ListUsersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_ui_user_list';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    _mm_ui_userlist_setup(array(0 => ''), $form, 'userlist', $this->t("User's name:"), TRUE, '');
    $form['userlist-choose']['#title'] = '';
    $form['userlist']['#mm_list_hide_left_pane'] = TRUE;
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('View user'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = mm_ui_mmlist_key0($form_state->getValue('userlist'));

    if (isset($uid) && ($home = mm_content_get(array('f.flag' => 'user_home', 'f.data' => $uid), Constants::MM_GET_FLAGS)) && !mm_content_is_recycled($home[0]->mmtid)) {
      mm_set_form_redirect_to_mmtid($form_state, $home[0]->mmtid);
    }
    else if (($usr = User::load($uid)) && $usr->status) {
      $form_state->setResponse(new RedirectResponse(Url::fromRoute('entity.mm_tree.canonical', ['mm_tree' => mm_content_users_mmtid()])->toString() . '/' . $usr->getUsername()));
    }
    else {
      \Drupal::messenger()->addStatus($this->t('The selected user does not have a homepage.'), 'error');
    }
  }

}
