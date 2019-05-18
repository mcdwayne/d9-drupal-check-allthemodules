<?php

namespace Drupal\mailman_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\mailman_integration\MailmanIntegration;

/**
 * Mail list subcribe form.
 */
class MailmanIntegrationSubcribeUserForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailman_integration_subcribe_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $list_name = NULL) {
    $form['#tree'] = TRUE;
    $form['add_list'] = array(
      '#type' => 'markup',
      '#prefix' => '<div id="lists-fieldset-wrapper">',
      '#suffix' => '</div>',
    );
    $list_val = mailman_integration_get_list_general($list_name);
    if (!isset($list_val['real_name'])) {
      throw new NotFoundHttpException();
    }
    $form['add_list']['name_of_list'] = array(
      '#type' => 'hidden',
      '#name' => 'name_of_list',
      '#value' => $list_name,
    );
    $form['add_list']['list_mail_address'] = array(
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => $this->t('Initial list E-Mail address'),
      '#default_value' => isset($list_val['owner']) ? $list_val['owner'] : '',
      '#description' => $this->t('Multiple E-Mail separated by new line.'),
    );
    $description = isset($list_val['description']) ? $list_val['description'] : '';
    $desc        = mailman_integration_match_desc($description);
    $form['add_list']['list_description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('List Description'),
      '#default_value' => $desc['description'],
      '#rows' => 2,
    );
    $params = array();
    $params['entity_id']    = '';
    $params['entity_type']  = '';
    $params['bundle']       = '';
    $params['listname']     = $list_name;
    $list                   = \Drupal::service('mailman_integration.mailman_controler')->selectListData($params);
    $form['add_list']['user_can_subscribe'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('User can Subscribe'),
      '#description' => $this->t('If checked, User can Subscribe this List.'),
      '#default_value' => $list[0]->visible_to_user,
    );
    $form['add_list']['userblock'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Add Users'),
      '#attributes' => array('class' => array('mailman-user-block')),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $num_user = $form_state->get('no_of_users');
    if (empty($num_user)) {
      $num_user = 1;
      $form_state->set('no_of_users', 1);
    }
    for ($i = 0; $i < $num_user; $i++) {
      $div_start = ($i == ($num_user - 1)) ? '<div id="subscribe-block">' : '';
      $form['add_list']['userblock']['user_name'][$i] = array(
        '#type' => 'textfield',
        '#title' => $this->t('User Name'),
        '#autocomplete_route_name' => 'mailman_integration.user_ac_callback',
        '#prefix' => $div_start,
      );
    }
    $form['add_list']['userblock']['add_name'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add User'),
      '#name' => 'add_more',
      '#submit' => ['::userAddOne'],
      '#ajax' => [
        'callback' => [$this, 'listUserCallback'],
        'wrapper' => 'lists-fieldset-wrapper',
      ],
    );
    if ($num_user > 1) {
      $form['add_list']['userblock']['remove_name'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_one',
        '#submit' => ['::listUserRemoveOne'],
        '#ajax' => [
          'callback' => [$this, 'listUserCallback'],
          'wrapper' => 'lists-fieldset-wrapper',
        ],
      );
    }
    $form['add_list']['userblock']['moreuser_mockup'] = array(
      '#type' => 'markup',
      '#markup' => '</div>',
    );
    $form['add_list']['link_mockup_start'] = array(
      '#type' => 'markup',
      '#markup' => '<div class="submit-link-part">',
    );
    $form['add_list']['submit'] = array(
      '#type' => 'submit',
      '#name' => 'add_userlist',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    );
    $url_cancel = Url::fromRoute('mailman_integration.view_list');
    $view_list_link = \Drupal::l($this->t('Cancel'), $url_cancel);
    $form['add_list']['link_mockup'] = array(
      '#type' => 'markup',
      '#markup' => $view_list_link,
    );
    $form['add_list']['link_mockup_end'] = array(
      '#type' => 'markup',
      '#markup' => '</div>',
    );
    $form_state->setCached(FALSE);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    if ($trigger_element['#name'] == 'remove_one' || $trigger_element['#name'] == 'add_more') {
      return;
    }
    else {
      $bulk_emails = $form_state->getValue(array('add_list', 'list_mail_address'));
      $list_name   = $form_state->getValue(array('add_list', 'name_of_list'));
      $mail_addrs  = preg_split('/(\r?\n)+/', $bulk_emails);
      foreach ($mail_addrs as $key => $mail) {
        $mail = trim($mail);
        if ($mail && !valid_email_address($mail)) {
          $form_state->setErrorByName('add_list][list_mail_address', $this->t('%mail - Email address appears to be invalid.', ['%mail' => $mail]));
        }
      }
      if (count($mail_addrs) !== count(array_unique($mail_addrs))) {
        $form_state->setErrorByName('add_list][list_mail_address', $this->t('Repeated Email address not allowed.'));
      }
      $user_values = $form_state->getValue(
        array('add_list', 'userblock', 'user_name')
      );
      foreach ($user_values as $key => $user_val) {
        if ($user_val) {
          $user = user_load_by_name($user_val);
          if (!$user) {
            $form_state->setErrorByName('add_list][userblock][user_name][' . $key, $this->t('Invalid User - %val', ['%val' => $user_val]));
          }
          else {
            $already_member = mailman_integration_is_member_inlist($list_name, $user->getEmail());
            if (count($already_member)) {
              $form_state->setErrorByName('add_list][userblock][user_name][' . $key, $this->t('User already Exists - %val', ['%val' => $user_val]));
            }
          }
        }
      }
      $list_val = mailman_integration_get_list_general($list_name);
      if (!isset($list_val['real_name']) || strtolower($list_val['real_name']) != strtolower($list_name)) {
        $form_state->setErrorByName('name_of_list', $this->t('Invalid Mailman List'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    if ($trigger_element['#name'] == 'remove_one' || $trigger_element['#name'] == 'add_more') {
      return;
    }
    $list_name   = $form_state->getValue(array('add_list', 'name_of_list'));
    // Update the list owner and description.
    $admin_url = mailman_integration_get_admin_url();
    $authenticate_pass = mailman_integration_get_auth_pass();
    $mailman = new MailmanIntegration($admin_url, '', $authenticate_pass, $list_name);
    $upd_params = array();
    $list_desc  = $form_state->getValue(array('add_list', 'list_description'));
    $list_mails = $form_state->getValue(array('add_list', 'list_mail_address'));
    $upd_params['description'] = $list_desc;
    $upd_params['owner']       = $list_mails;
    $upd_params['real_name']   = $list_name;
    $mailman->mailmanListUpdate($upd_params);
    // Update the list owners in custom mailman data.
    $upd_custom                = array();
    $upd_custom['list_name']   = $list_name;
    $upd_custom['list_owner']  = $list_mails;
    $upd_custom['list_desc']   = $list_desc;
    $upd_custom['visible_list'] = $form_state->getValue(array(
      'add_list', 'user_can_subscribe',
    ));
    \Drupal::service('mailman_integration.mailman_controler')->updateListData($upd_custom);
    $user_values = $form_state->getValue(array(
      'add_list', 'userblock', 'user_name',
    ));
    foreach ($user_values as $user_val) {
      if ($user_val) {
        $user = user_load_by_name($user_val);
        if ($user) {
          $user_mail = $user->getEmail();
          $already_member = mailman_integration_is_member_inlist($list_name, $user_mail);
          if (!count($already_member)) {
            mailman_integration_subscribe($list_name, $user_mail);
            // Update user option.
            mailman_integration_set_user_option($list_name, $user_mail, 'fullname', $user->getAccountName());
            \Drupal::service('mailman_integration.mailman_controler')->insertUsers($list_name, $user_mail, 0, $user->id());
          }
        }
        if (!$list_name) {
          $form_state->setErrorByName('name_of_list', $this->t('Error in user Subscribe'));
        }
      }
    }
  }

  /**
   * Calback function for add more user.
   */
  public function listUserCallback(array $form, FormStateInterface $form_state) {
    return $form['add_list'];
  }

  /**
   * Increments the max counter and causes a rebuild.
   */
  public function userAddOne($form, FormStateInterface $form_state) {
    $num_user = $form_state->get('no_of_users');
    $form_state->set('no_of_users', $num_user + 1);
    $form_state->setRebuild();
  }

  /**
   * Decrements the max counter and causes a form rebuild.
   */
  public function listUserRemoveOne($form, FormStateInterface $form_state) {
    $num_user = $form_state->get('no_of_users');
    if ($num_user > 1) {
      $form_state->set('no_of_users', $num_user - 1);
    }
    $form_state->setRebuild();
  }

}
