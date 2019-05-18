<?php

namespace Drupal\clu\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * CLUListingUsers form.
 */
class CLUListingUsers extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'listing_users';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $search_value = \Drupal::request()->query->get('search_user');
    $user = \Drupal::currentUser();
    $form['clu'] = [
      '#type' => 'fieldset',
      '#title' => t('Search'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['clu']['search_user'] = [
      '#type' => 'textfield',
      '#title' => 'Search by Username',
      '#default_value' => $search_value,
    ];
    $form['clu']['search_submit'] = [
      '#type' => 'submit',
      '#value' => 'Search',
    ];
    $form['clu']['clear_submit'] = [
      '#type' => 'submit',
      '#value' => 'Clear',
    ];
    $header = [
      'user' => t('User'),
      'loggedin_since' => t('Loggedin Since'),
      'session' => t('Session Details'),
      'action' => t('OPERATIONS'),
    ];

    $num_per_page = 10;
    $page = pager_find_page();

    $query = \Drupal::database()->select('sessions', 's');
    $query->join('users_field_data', 'u', 's.uid = u.uid');
    $query->fields('u', ['uid']);
    $query->fields('u', ['name']);
    $query->fields('u', ['mail']);
    $query->fields('s', ['timestamp']);
    $query->fields('s', ['hostname']);
    $query->fields('s', ['sid']);
    $query->orderBy('uid', 'DESC');
    $query->distinct();

    if (isset($search_value) && !empty($search_value)) {
      $query->condition('u.name', \Drupal::database()->escapeLike($search_value) . '%', 'LIKE');
    }

    $pager_query = $query;
    $pager_query = $pager_query->execute()->fetchAll();
    $pager_total = count($pager_query);
    $offset = $num_per_page * $page;
    if ($pager_total >= $num_per_page) {
      pager_default_initialize($pager_total, $num_per_page);
      $query->range($offset, $num_per_page);
    }
    $results = $query->execute()->fetchAll();
    $output = '';
    $clu_end_selected = '';
    foreach ($results as $result) {
      if ($user->id() != $result->uid) {
        $url = Url::fromUserInput('/admin/people/clu/' . $result->uid);
        $url->setOptions(['attributes' => ['class' => ['button']]]);
        $action = Link::fromTextAndUrl(t('End session'), $url);
      }
      else {
        $url = Url::fromUserInput('/user/logout');
        $url->setOptions(['attributes' => ['class' => ['button']]]);
        $action = Link::fromTextAndUrl(t('Logout'), $url);
      }
      $session_url = Url::fromUserInput('/admin/people/clu/session/' . $result->sid);
      $session_url->setOptions(['attributes' => ['class' => ['button']]]);
      $output[$result->sid] = [
        'user' => $result->name . ' (' . $result->mail . ')',
        'loggedin_since' => $this->getLastLoginTime($result->timestamp),
        'session' => Link::fromTextAndUrl(t('Session Details'), $session_url),
        'action' => $action,
      ];
      $clu_end_selected[$result->uid] = $result->uid;
    }
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $output,
      '#empty' => t('No users found'),
    ];
    $form['clu_end_selected'] = [
      '#type' => 'hidden',
      '#value' => $clu_end_selected,
    ];
    $form['pager'] = [
      '#type' => 'pager'
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'End sessions',
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('op') == 'End sessions') {
      if (empty($form_state->getValue('table'))) {
        $form_state->setErrorByName('clu_table', $this->t('Please select atleast one user to terminate the session.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search_user = $form_state->getValue('search_user');
    \Drupal::request()->query->set('search_user', $search_user);
    if ($form_state->getValue('op') == 'End sessions') {
      foreach ($form_state->getValue('clu_end_selected') as $delete_session_uid) {
        $user = User::load($delete_session_uid);
        if (\Drupal::currentUser()->id() != $delete_session_uid && $delete_session_uid != 0) {
          \Drupal::database()->delete('sessions')
              ->condition('uid', $delete_session_uid)
              ->execute();
          drupal_set_message(t('@username ( @userid ) user session has been ended.', [
            '@username' => $user->getAccountName(),
            '@userid' => $user->id(),
          ]));
        }
      }
    }
    if ($form_state->getValue('op') == 'Clear') {
      $form_state->setRedirect('clu.c_l_u_listing_users');
    }
  }

  /**
   * Callback function for getting last login time.
   *
   * @param $time
   *   The timestamp
   *
   * @return string
   *   The logged in time.
   */
  protected function getLastLoginTime($time) {
    $second = 1;
    $minute = 60 * $second;
    $hour = 60 * $minute;
    $day = 24 * 60 * 60 * 1;
    $difference = time() - $time;
    $days = !empty(floor($difference / $day)) ? floor($difference / $day) . ' days ' : '';
    $hours = !empty(floor(($difference % $day) / $hour)) ? floor(($difference % $day) / $hour) . ' hours ' : '';
    $minutes = !empty(floor((($difference % $day) % $hour) / $minute)) ? floor((($difference % $day) % $hour) / $minute) . ' minutes ' : '';
    $seconds = !empty(floor(((($difference % $day) % $hour) % $minute) / $second)) ? floor(((($difference % $day) % $hour) % $minute) / $second) . ' seconds' : '';
    return $days . $hours . $minutes . $seconds;
  }

}
