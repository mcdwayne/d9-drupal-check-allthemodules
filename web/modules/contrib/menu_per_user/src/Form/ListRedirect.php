<?php

namespace Drupal\menu_per_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

class ListRedirect extends FormBase {

  public function getFormId() {
    return 'menu_per_user_list_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $menu_path = \Drupal::request()->request->get('menu_path');
    $form['path'] = array(
      '#title' => t('Menu Path'),
      '#type' => 'textfield',
      '#default_value' => isset($menu_path) ? $menu_path : '',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Filter'),
    );
    $form['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
    );
    $connection = \Drupal::database();
    $query = $connection->select('user_menu_visibility', 'm');
    $query->fields('m');
    if (!empty($menu_path)) {
      $query->condition('menu_path', '%' . $connection->escapeLike($menu_path) . '%', 'LIKE');
    }
    $results = $query->execute()->fetchAll();
    $path = array();
    $menu = array();
    $str = [];
    $output = [];
    foreach ($results as $result) {
      // Edit link.
      $edit_link = $base_url . '/admin/config/menu_per_user/edit?menu_path=' . $result->menu_path;
      $edit_url = Url::fromUri($edit_link);
      // Delete link.
      $delete_link = $base_url . '/admin/config/menu_per_user/delete?menu_path=' . $result->menu_path;
      $delete_url = Url::fromUri($delete_link);
      $menu['menu_path'] = $result->menu_path;
      $path[$result->menu_path] = $result->menu_path;
      if (($result->user_id != 0) && (isset($path[$result->menu_path]))) {
        $menu['users'] .= $result->user_id . ',';
      }
      if (($result->user_id == 0)) {
        $anonymous[$result->menu_path]['anonymous'] = t('Yes');
      }
      elseif (($result->user_id != 0) && !isset($anonymous[$result->menu_path]['anonymous'])) {
        $anonymous[$result->menu_path]['anonymous'] = t('No');
      }
      $str = implode(',', array_unique(explode(',', $menu['users'])));
      $menu['users'] = $str;
      $output[$result->menu_path]['users'] = rtrim($menu['users'], ',');
      $output[$result->menu_path]['path'] = $result->menu_path;
      $output[$result->menu_path]['anonymous'] = $anonymous[$result->menu_path]['anonymous'];
      $output[$result->menu_path]['edit'] = \Drupal::l(t('Edit'), $edit_url, array('external' => TRUE));
      $output[$result->menu_path]['delete'] = \Drupal::l(t('Delete'), $delete_url, array('external' => TRUE));
    }
    $header = [
      array('data' => t('User ID(s)')),
      array('data' => t('Menu Path')),
      array('data' => t('Anonymous')),
      array('data' => t('Edit link')),
      array('data' => t('Delete link')),
    ];
    $form['list_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $output,
      '#empty' => t('No Data found'),
    ];
    $form['pager'] = array(
      '#type' => 'pager'
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Goto current path if reset.
    if ((string) $values['op'] == t('Reset')) {
      menu_per_user_redirect(\Drupal::url('menu_per_user.list_redirects'));
    }
    // Pass values to url.
    if ((string) $values['op'] == t('Filter')) {
      $filter_path = $values['path'];
      $params['menu_path'] = Html::escape($filter_path);
      $url = Url::fromRoute('menu_per_user.list_redirects')->setRouteParameters(['menu_path' => Html::escape($filter_path)]);
      $form_state->setRedirectUrl($url);
    }
  }

}
