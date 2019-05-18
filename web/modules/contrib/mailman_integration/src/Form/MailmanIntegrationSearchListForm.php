<?php

namespace Drupal\mailman_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Search mail list form.
 */
class MailmanIntegrationSearchListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailman_integration_search_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $ptions = array(
      'all'     => 'All',
      'manual'  => 'Manual',
      'role'    => 'Role',
    );
    $list_type = \Drupal::request()->query->get('type', 'all');
    $list_name = \Drupal::request()->query->get('name', '');
    $form['list_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('List Type'),
      '#options' => $ptions,
      '#default_value' => $list_type,
    );
    $form['name'] = array(
      '#title' => $this->t('Name/Email'),
      '#type' => 'textfield',
      '#attributes' => array(
        'id' => 'search-name',
      ),
      '#default_value' => $list_name,
    );
    $form['submit'] = array(
      '#value' => 'Search',
      '#type' => 'submit',
      '#prefix' => '<div id="list-search-submit">',
      '#limit_validation_errors' => array(),
      '#name' => 'search_submit',
    );
    $form['reset_but'] = array(
      '#value' => 'Reset',
      '#type' => 'submit',
      '#suffix' => '</div>',
      '#attributes' => array(
        'class' => array('btn-primary'),
        'id' => 'reset-submit',
      ),
      '#name' => 'search_reset',
      '#limit_validation_errors' => array(),
    );
    $header = [
      [
        'data' => $this->t('Name'),
        'width' => '20%',
        'field' => 'list_name',
        'sort' => 'asc',
      ],
      ['data' => $this->t('Email'), 'width' => '30%', 'field' => 'list_owners'],
      ['data' => $this->t('Description'), 'width' => '30%'],
      ['data' => $this->t('Action'), 'width' => '20%'],
    ];
    $lists = \Drupal::service('mailman_integration.mailman_controler')->getMailmanSearchReasults($list_type, $list_name, $header);
    $rows = array();
    foreach ($lists as $key => $list) {
      $desc = mailman_integration_match_desc($list->description);
      $link = '<div>';
      if ($list->bundle == 'role') {
        $url_add_user = Url::fromRoute('mailman_integration.add_role_user_callback', array('list_name' => $list->list_name));
        $link .= \Drupal::l($this->t('Add/View Users'), $url_add_user);
      }
      else {
        $url_add_user = Url::fromRoute('mailman_integration.add_user_callback', array('list_name' => $list->list_name));
        $link .= \Drupal::l($this->t('Add/View Users'), $url_add_user);
      }
      if (\Drupal::currentUser()->hasPermission('administer mailman_integration')) {
        $link .= '<span> | </span>';
        $url_delete = Url::fromRoute('mailman_integration.list_delete_confirm', array('list_name' => $list->list_name));
        $link .= \Drupal::l($this->t('Delete'), $url_delete);
      }
      $link .= '</div>';
      $owner = nl2br($list->list_owners);
      $rows[] = array(
        'class' => array('list-name-' . $key),
        'data' => array(
          $list->list_name,
          SafeMarkup::format($owner, array()),
          SafeMarkup::checkPlain($desc['description']),
          SafeMarkup::format($link, array()),
        ),
      );
    }
    $form['table']  = array(
      '#type'      => 'table',
      '#header'     => $header,
      '#rows'       => $rows,
      '#attributes' => array('class' => array('mailman-list'), 'id' => 'mailman-list-table'),
      '#empty'      => $this->t('No List found.'),
    );
    $form['pager']  = array('#type' => 'pager');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    if ($trigger_element['#name'] == 'search_reset') {
      $form_state->setRedirectUrl($this->getResetUrl());
    }
    elseif ($trigger_element['#name'] == 'search_submit') {
      $list_type = $form_state->getValue(['list_type']);
      $name      = $form_state->getValue(['name']);
      $qs = array();
      if (isset($list_type) && $list_type) {
        $qs['type'] = $list_type;
      }
      if (isset($name) && $name) {
        $qs['name'] = $name;
      }
      $current_path = \Drupal::url('<current>', [], ['absolute' => FALSE, 'query' => $qs]);
      $response = new RedirectResponse($current_path);
      $response->send();
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResetUrl() {
    return new Url('mailman_integration.view_list');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
