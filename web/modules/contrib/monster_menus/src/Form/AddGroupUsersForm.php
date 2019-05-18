<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\AddGroupUsersForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

class AddGroupUsersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_ui_large_group_add_users';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    list($instance, $link_id, $title, $button_text, $click_func_name, $owner_uid, $owner_name) = $form_state->getBuildInfo()['args'];
    if (!$this->currentUser()->hasPermission('access user profiles')) {
      $form[] = _mm_ui_no_add_user();
      return $form;
    }

    $single = is_null($owner_uid) ? 0 : 1;
    $form['selectuser']["adduser-choose$instance"] = array(
      '#type' => 'textfield',
      '#title' => $single ? $this->t('Choose the owner') : $this->t('Add a user'),
      '#autocomplete_route_name' => 'monster_menus.autocomplete',
      '#description' => mm_autocomplete_desc(),
      '#size' => 30,
      '#maxlength' => 40,
      '#attributes' => ['autofocus' => 'autofocus'],
    );

    $users = array();
    if ($single) {
      $users[$owner_uid] = $owner_name;
    }
    $form['selectuser']["adduser$instance"] = array(
      '#type' => 'mm_userlist',
      '#title' => $title,
      '#required' => $single,
      '#default_value' => $users,
      '#mm_list_autocomplete_name' => "adduser-choose$instance",
      '#mm_list_min' => $single,
      '#mm_list_max' => $single,
      '#mm_list_submit_on_add' => $single,
    );
    $form["adduser$instance-submit"] = array(
      '#name' => "adduser$instance-submit",
      '#type' => 'submit',
      '#value' => $button_text,
    );
    $attr = New Attribute($form['#attributes']);
    $attr['onsubmit'] = "return $click_func_name(jQuery(this).find('.mm-list-hidden'), '$link_id');";
    $form['#attributes'] = $attr->toArray();
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
