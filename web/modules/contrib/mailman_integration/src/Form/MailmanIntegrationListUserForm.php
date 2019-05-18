<?php

namespace Drupal\mailman_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Mailman list user form.
 */
class MailmanIntegrationListUserForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailman_integration_list_users';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $list_name = NULL) {
    $form = [];
    if (!$list_name) {
      return array();
    }
    $search_input = \Drupal::request()->query->get('name', '');
    $form['search'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Search Users'),
      '#attributes' => array('class' => array('mailman-role-block')),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['search']['name'] = array(
      '#title' => $this->t('Name/Email'),
      '#type' => 'textfield',
      '#attributes' => array(
        'id' => 'search-name',
      ),
      '#default_value' => $search_input,
    );
    $form['search']['submit'] = array(
      '#value' => $this->t('Search'),
      '#type'  => 'submit',
      '#prefix' => '<div id="search-submit">',
      '#name'  => 'search_submit',
    );
    $form['search']['reset'] = array(
      '#value'  => $this->t('Reset'),
      '#type'   => 'submit',
      '#suffix' => '</div>',
      '#attributes' => array(
        'class' => array('btn-primary'),
        'id'    => 'reset-submit',
      ),
      '#name'   => 'reset',
    );
    $list_id = mailman_integration_get_list_id($list_name);
    $header = array(
      'name' => array(
        'data' => $this->t('Name'),
        'width' => '40%',
        'field' => 'name',
        'sort' => 'asc',
      ),
      'mail' => array(
        'data' => $this->t('Email'),
        'width' => '50%',
        'field' => 'mail',
      ),
    );
    if ($search_input) {
      $lists_qry = \Drupal::service('mailman_integration.mailman_controler')->selectListUsers($list_id, $search_input);
    }
    else {
      $lists_qry = \Drupal::service('mailman_integration.mailman_controler')->selectListUsers($list_id);
    }
    $select_stmt = $lists_qry->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header)->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(\Drupal::config('mailman_integration.settings')->get('mailman_integration_list_pagination'));
    $lists = $select_stmt->execute();
    $rows = array();
    foreach ($lists as $list) {
      $rows[$list->mail] = array(
        'name' => array('data' => $list->name),
        'mail' => array('data' => $list->mail),
      );
    }
    $form['user_list']['name_of_list'] = array(
      '#type' => 'hidden',
      '#name' => 'name_of_list',
      '#value' => $list_name,
    );
    $form['user_list']['mailman_listid'] = array(
      '#type' => 'hidden',
      '#name' => 'mailman_listid',
      '#value' => $list_id,
    );
    $form['user_list']['user_table'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#empty' => $this->t('No users found'),
      '#attributes' => array('class' => array('list-mailman-user')),
    );
    if (count($rows)) {
      $form['user_list']['submit'] = array(
        '#type' => 'submit',
        '#name' => 'delete_selected',
        '#value' => $this->t('Delete Selected'),
      );
    }
    $form['pager'] = array('#type' => 'pager');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    if ($trigger_element['#name'] == 'delete_selected') {
      $delete_list = array_filter($form_state->getValue('user_table'));
      if (!count($delete_list)) {
        $form_state->setErrorByName('', $this->t('Choose any one of user.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    if ($trigger_element['#name'] == 'delete_selected') {
      $delete_list = array_filter($form_state->getValue('user_table'));
      $list_name   = $form_state->getValue('name_of_list');
      $list_id     = $form_state->getValue('mailman_listid');
      foreach ($delete_list as $mail => $name) {
        mailman_integration_unsubscribe($list_name, $mail);
        // Remove from mailman user table.
        \Drupal::service('mailman_integration.mailman_controler')->removeListUsers($list_name, $mail, $list_id);
      }
    }
    elseif ($trigger_element['#name'] == 'reset') {
      $current_path = \Drupal::url('<current>', [], ['absolute' => FALSE]);
      $response = new RedirectResponse($current_path);
      $response->send();
      return;
    }
    elseif ($trigger_element['#name'] == 'search_submit') {
      $name  = $form_state->getValue('name');
      $qs = array();
      if (isset($name) && $name) {
        $qs['name'] = $name;
      }
      $current_path = \Drupal::url('<current>', [], ['absolute' => FALSE, 'query' => $qs]);
      $response = new RedirectResponse($current_path);
      $response->send();
      return;
    }
  }

}
