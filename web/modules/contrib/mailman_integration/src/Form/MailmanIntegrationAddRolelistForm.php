<?php

namespace Drupal\mailman_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\mailman_integration\MailmanIntegration;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Session\AccountInterface;

/**
 * Create role based mail list form.
 */
class MailmanIntegrationAddRolelistForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailman_integration_add_rolelist_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $list_name = NULL) {
    $form = [];
    $form['add_roles'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="lists-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    $list_id = '';
    if ($list_name) {
      $list_val = mailman_integration_get_list_general($list_name);
      if (!isset($list_val['real_name'])) {
        throw new NotFoundHttpException();
      }
      $form['add_roles']['name_of_list'] = [
        '#type' => 'hidden',
        '#name' => 'name_of_list',
        '#value' => $list_name,
      ];
      $description = isset($list_val['description']) ? $list_val['description'] : '';
      $desc = mailman_integration_match_desc($description);
      $form['add_roles']['list_description'] = [
        '#type' => 'textarea',
        '#title' => $this->t('List Description'),
        '#default_value' => $desc['description'],
        '#rows' => 2,
      ];
      $form['add_roles']['list_mail_address'] = [
        '#type' => 'textarea',
        '#required' => TRUE,
        '#title' => $this->t('Initial list E-Mail address'),
        '#default_value' => isset($list_val['owner']) ? $list_val['owner'] : '',
        '#description' => $this->t('Multiple E-Mail separated by new line.'),
      ];
      $list_id = mailman_integration_get_list_id($list_name);
    }
    else {
      $form['add_roles']['name_of_list'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Name of Role List'),
      ];
      $form['add_roles']['list_description'] = [
        '#type' => 'textarea',
        '#title' => $this->t('List Description'),
        '#rows' => 2,
      ];
      $form['add_roles']['list_mail_address'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Initial list E-Mail address'),
      ];
      $form['add_roles']['initial_list_password'] = [
        '#type' => 'password',
        '#required' => TRUE,
        '#title' => $this->t('Initial list password'),
      ];
    }
    $form['add_roles']['mailman_listid'] = [
      '#type' => 'hidden',
      '#name' => 'mailman_listid',
      '#value' => $list_id,
    ];
    $form['add_roles']['mm_form_type'] = [
      '#type' => 'hidden',
      '#name' => 'mm_form_type',
      '#value' => ($list_name) ? 'edit' : 'add',
    ];
    $form['add_roles']['roleblock'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add Roles'),
      '#attributes' => ['class' => ['mailman-role-block']],
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $roles = [];
    foreach (user_roles(TRUE) as $rid => $name) {
      $roles[$rid] = $name->label();
    }
    $drupal_auth_role_id = $roles[AccountInterface::AUTHENTICATED_ROLE];
    $checkbox_authenticated = [
      '#type' => 'checkbox',
      '#title' => SafeMarkup::checkPlain($drupal_auth_role_id),
      '#default_value' => TRUE,
      '#disabled' => TRUE,
    ];
    $mailman_roles = [];
    if ($list_name) {
      $params = [];
      $params['role_id'] = '';
      $params['list_id'] = '';
      $params['listname'] = $list_name;
      $params['role_list'] = 1;
      $mailman_roles = \Drupal::service('mailman_integration.mailman_controler')->selectListRoles($params);
    }
    $form['add_roles']['roleblock']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => (count($mailman_roles)) ? $mailman_roles : [],
      '#options' => $roles,
      AccountInterface::AUTHENTICATED_ROLE => $checkbox_authenticated,
    ];
    $form['add_roles']['link_mockup_start'] = [
      '#type' => 'markup',
      '#markup' => '<div class="submit-link-part">',
    ];
    $form['add_roles']['submit'] = [
      '#type' => 'submit',
      '#name' => 'add_rolelist',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];
    $url_cancel = Url::fromRoute('mailman_integration.view_list');
    $view_list_link = \Drupal::l($this->t('Cancel'), $url_cancel);
    $form['add_roles']['link_mockup'] = [
      '#type' => 'markup',
      '#markup' => $view_list_link,
    ];
    $form['add_roles']['link_mockup_end'] = [
      '#type' => 'markup',
      '#markup' => '</div>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $list_name = $form_state->getValue('name_of_list');
    $form_type = $form_state->getValue('mm_form_type');
    $error = 0;
    $connection_status = mailman_integration_connection_status();
    if (!$connection_status) {
      $form_state->setErrorByName('', $this->t('Unable to connect Mailman.'));
      $error = 1;
    }
    if ($form_type == 'add') {
      if (preg_match('/[^A-Za-z0-9-_]/', $list_name)) {
        $form_state->setErrorByName('name_of_list', $this->t('Name of List accept only Alphanumeric, underscore(_) and hyphen(-)'));
        $error = 1;
      }
      if ($form_state->getValue('list_mail_address')) {
        if (!valid_email_address($form_state->getValue(['list_mail_address']))) {
          $form_state->setErrorByName('list_mail_address', $this->t('The Email address appears to be invalid.'));
          $error = 1;
        }
      }
      if (!$form_state->getValue('list_mail_address') || !$list_name || !$form_state->getValue('initial_list_password')) {
        $error = 1;
      }
    }
    else {
      $bulk_emails = $form_state->getValue('list_mail_address');
      $mail_addrs = preg_split('/(\r?\n)+/', $bulk_emails);
      foreach ($mail_addrs as $mail) {
        $mail = trim($mail);
        if ($mail && !valid_email_address($mail)) {
          $form_state->setErrorByName('list_mail_address', $this->t('%mail - Email address appears to be invalid.', ['%mail' => $mail]));
          $error = 1;
        }
      }
      if (count($mail_addrs) !== count(array_unique($mail_addrs))) {
        $form_state->setErrorByName('list_mail_address', $this->t('Repeated Email address not allowed.'));
        $error = 1;
      }
      $list_val = mailman_integration_get_list_general($list_name);
      if (!isset($list_val['real_name']) || strtolower($list_val['real_name']) != strtolower($list_name)) {
        $form_state->setErrorByName('name_of_list', $this->t('Invalid Mailman List'));
        $error = 1;
      }
      if (!$form_state->getValue(['list_mail_address']) || !$list_name) {
        $error = 1;
      }
    }
    if (!$error && $trigger_element['#name'] == 'add_rolelist') {
      if ($form_type == 'add') {
        $result = mailman_integration_createlist($form_state->getValues());
        if ($result['status']) {
          $params = [];
          $params['entity_id'] = '';
          $params['entity_type'] = 'role';
          $params['bundle'] = 'role';
          $params['listname'] = $list_name;
          $params['list_owner'] = $form_state->getValue('list_mail_address');
          $params['list_desc'] = $form_state->getValue('list_description');
          $mailman_id = \Drupal::service('mailman_integration.mailman_controler')->insertListData($params);
        }
      }
      else {
        $admin_url = mailman_integration_get_admin_url();
        $authenticate_pass = mailman_integration_get_auth_pass();
        $mailman = MailmanIntegration::getInstance($admin_url, '', $authenticate_pass, $list_name);
        $list_desc = $form_state->getValue('list_description');
        $upd_params = [];
        $upd_params['description'] = $list_desc;
        $upd_params['owner'] = $form_state->getValue('list_mail_address');
        $upd_params['real_name'] = $list_name;
        $mailman->mailmanListUpdate($upd_params);
        // Update the list owners in custom mailman data.
        $upd_custom = [];
        $upd_custom['list_name'] = $list_name;
        $upd_custom['list_owner'] = $form_state->getValue('list_mail_address');
        $upd_custom['list_desc'] = $list_desc;
        \Drupal::service('mailman_integration.mailman_controler')->updateListData($upd_custom);
        $result['status'] = 1;
        $mailman_id = $form_state->getValue(['mailman_listid']);
      }
      if (!$result['status']) {
        $form_state->setErrorByName('name_of_list', $this->t('Unable to create Mailman List. %msg', ['%msg' => $result['msg']]));
      }
      else {
        $form_value_roles = $form_state->getValue('roles');
        if (($key = array_search(AccountInterface::ANONYMOUS_ROLE, $form_value_roles)) !== FALSE) {
          unset($form_value_roles[$key]);
        }
        $roles = array_flip($form_value_roles);
        unset($roles[0]);
        $submitted_roles = array_keys($roles);
        $old_roles = [];
        if ($list_name) {
          $params = [];
          $params['role_id'] = '';
          $params['list_id'] = '';
          $params['listname'] = $list_name;
          $params['role_list'] = 1;
          $old_roles = \Drupal::service('mailman_integration.mailman_controler')->selectListRoles($params);
        }
        $removed_role = array_diff($old_roles, $submitted_roles);
        $added_role = array_diff($submitted_roles, $old_roles);
        if (count($added_role) || count($removed_role)) {
          if (count($added_role)) {
            $old_role_diff = [];
            $added_role_diff = [];
            $old_role_list = \Drupal::service('mailman_integration.mailman_controler')->getRoleList($old_roles, 1);
            foreach ($old_role_list as $mail => $row) {
              $old_role_diff[$mail] = $row['name'];
            }
            $added_role_list = \Drupal::service('mailman_integration.mailman_controler')->getRoleList($added_role, 1);
            foreach ($added_role_list as $mail => $row) {
              $added_role_diff[$mail] = $row['name'];
            }
            $want_to_add_mailman = array_diff($added_role_diff, $old_role_diff);
            if (count($want_to_add_mailman)) {
              // Subcribe $want_to_add_mailman list.
              foreach ($want_to_add_mailman as $mail => $name) {
                $already_member = mailman_integration_is_member_inlist($list_name, $mail);
                if (!count($already_member)) {
                  mailman_integration_subscribe($list_name, $mail);
                  // Update user option.
                  mailman_integration_set_user_option($list_name, $mail, 'fullname', $name);
                  // Insert into mailman user table.
                  \Drupal::service('mailman_integration.mailman_controler')->insertUsers($list_name, $mail, $mailman_id, $added_role_list[$mail]['uid']);
                }
              }
            }
            foreach ($added_role as $role_val) {
              $params = [];
              $params['role_id'] = $role_val;
              $params['listname'] = $list_name;
              $params['list_id'] = $mailman_id;
              \Drupal::service('mailman_integration.mailman_controler')->insertListRoles($params);
            }
          }
          if (count($removed_role)) {
            $removed_role_list = \Drupal::service('mailman_integration.mailman_controler')->getRoleList($removed_role);
            $submitted_role_list = \Drupal::service('mailman_integration.mailman_controler')->getRoleList($submitted_roles);
            $want_to_remove_mailman = array_diff($removed_role_list, $submitted_role_list);
            if (count($want_to_remove_mailman)) {
              // Unsubcribe $want_to_remove_mailman list.
              foreach ($want_to_remove_mailman as $mail => $name) {
                mailman_integration_unsubscribe($list_name, $mail);
                // Remove from mailman user table.
                \Drupal::service('mailman_integration.mailman_controler')->removeListUsers($list_name, $mail, $mailman_id);
              }
            }
            foreach ($removed_role as $role_val) {
              $params = [];
              $params['role_id'] = $role_val;
              $params['list_id'] = $mailman_id;
              $params['listname'] = $list_name;
              \Drupal::service('mailman_integration.mailman_controler')->deleteListRole($params);
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('mailman_integration.add_role_user_callback',
      array('list_name' => $form_state->getValue('name_of_list'))
    );
  }

}
