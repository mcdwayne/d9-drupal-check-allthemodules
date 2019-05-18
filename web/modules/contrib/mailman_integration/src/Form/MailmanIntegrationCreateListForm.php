<?php

namespace Drupal\mailman_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Create manual mail list form.
 */
class MailmanIntegrationCreateListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailman_integration_create_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['add_list'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="lists-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['add_list']['name_of_list'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Name of List'),
    ];
    $form['add_list']['list_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List Description'),
      '#rows' => 2,
    ];
    $form['add_list']['list_mail_address'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Initial list E-Mail address'),
    ];
    $form['add_list']['user_can_subscribe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User can Subscribe'),
      '#description' => $this->t('If checked, User can Subscribe this List.'),
    ];
    $form['add_list']['initial_list_password'] = [
      '#type' => 'password',
      '#required' => TRUE,
      '#title' => $this->t('Initial list password'),
    ];
    $form['add_list']['userblock'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add Users'),
      '#attributes' => [
        'class' => ['mailman-user-block'],
      ],
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $num_user = $form_state->get('no_of_users');
    if (empty($num_user)) {
      $num_user = 1;
      $form_state->set('no_of_users', 1);
    }
    for ($i = 0; $i < $num_user; $i++) {
      $form['add_list']['userblock']['user_name'][$i] = [
        '#type' => 'textfield',
        '#title' => $this->t('User Name'),
        '#autocomplete_route_name' => 'mailman_integration.user_ac_callback',
      ];
    }
    $form['add_list']['userblock']['add_name'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add User'),
      '#name' => 'add_more',
      '#submit' => ['::userAddOne'],
      '#ajax' => [
        'callback' => [$this, 'listUserCallback'],
        'wrapper' => 'lists-fieldset-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];
    if ($num_user > 1) {
      $form['add_list']['userblock']['remove_name'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_one',
        '#submit' => ['::listUserRemoveOne'],
        '#ajax' => [
          'callback' => [$this, 'listUserCallback'],
          'wrapper' => 'lists-fieldset-wrapper',
        ],
      ];
    }
    $form['add_list']['submit'] = [
      '#type' => 'submit',
      '#name' => 'add_userlist',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];
    $url_cancel = Url::fromRoute('mailman_integration.view_list');
    $view_list_link = \Drupal::l($this->t('Back'), $url_cancel);
    $form['add_list']['link_mockup'] = [
      '#type' => 'markup',
      '#markup' => $view_list_link,
    ];
    $form_state->setCached(FALSE);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $list_name = $form_state->getValue(array('add_list', 'name_of_list'));
    if ($trigger_element['#name'] == 'add_more' || $trigger_element['#name'] == 'remove_one') {
      $form_state->setRebuild(FALSE);
    }
    else {
      $error = 0;
      $connection_status = mailman_integration_connection_status();
      if (!$connection_status) {
        $form_state->setErrorByName('', $this->t('Unable to connect Mailman.'));
        $error = 1;
      }
      if (preg_match('/[^A-Za-z0-9-_]/', $list_name)) {
        $form_state->setErrorByName('add_list][name_of_list', $this->t('Name of List accept only Alphanumeric, underscore(_) and hyphen(-)'));
        $error = 1;
      }
      $list_mail_address = $form_state->getValue(array('add_list', 'list_mail_address'));
      if ($list_mail_address) {
        if (!valid_email_address($list_mail_address)) {
          $form_state->setErrorByName('add_list][list_mail_address', $this->t('The Email address appears to be invalid.'));
          $error = 1;
        }
      }
      if (!$list_mail_address || !$list_name || !$form_state->getValue(array('add_list', 'initial_list_password'))) {
        $error = 1;
      }
      $user_values = $form_state->getValue(
        array('add_list', 'userblock', 'user_name')
      );
      foreach ($user_values as $key => $user_val) {
        if ($user_val) {
          $user = user_load_by_name($user_val);
          if (!$user) {
            $form_state->setErrorByName('add_list][userblock][user_name][' . $key, $this->t('Invalid User - %val', ['%val' => $user_val]));
            $error = 1;
          }
        }
      }
      if (!$error && $trigger_element['#name'] == 'add_userlist') {
        $list_values = $form_state->getValues();
        $result = mailman_integration_createlist($list_values['add_list']);
        if (!$result['status']) {
          $msg = isset($result['msg']) ? $result['msg'] : '';
          $form_state->setErrorByName('add_list][name_of_list', $this->t('Unable to create Mailman List. %msg', [
            '%msg' => $msg,
          ]));
        }
        else {
          // Insert the mailman list into  Mailman Integration custom table.
          $params = [];
          $params['entity_id'] = '';
          $params['entity_type'] = '';
          $params['bundle'] = 'manual';
          $params['listname'] = $list_name;
          $params['list_owner'] = $list_mail_address;
          $params['list_desc'] = $form_state->getValue(array('add_list', 'list_description'));
          $params['visible_list'] = $form_state->getValue(array('add_list', 'user_can_subscribe'));
          $mailman_id = \Drupal::service('mailman_integration.mailman_controler')->insertListData($params);
          foreach ($user_values as $key => $user_val) {
            if ($user_val) {
              $user = user_load_by_name($user_val);
              if ($user->getEmail()) {
                $already_member = mailman_integration_is_member_inlist($list_name, $user->getEmail());
                if (!count($already_member)) {
                  mailman_integration_subscribe($list_name, $user->getEmail());
                  // Update user option.
                  mailman_integration_set_user_option($list_name, $user->getEmail(), 'fullname', $user->getAccountName());
                  // Insert into mailman user table.
                  \Drupal::service('mailman_integration.mailman_controler')->insertUsers($list_name, $user->getEmail(), $mailman_id, $user->id());
                }
              }
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
    $form_state->setRedirect('mailman_integration.add_user_callback',
      array('list_name' => $form_state->getValue(array('add_list', 'name_of_list')))
    );
  }

  /**
   * Callback function for add more logic.
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
