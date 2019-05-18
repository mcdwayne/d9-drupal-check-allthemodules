<?php

/**
 * @file
 * Contains \Drupal\extra_siteinfo\Form\ExtraSiteinfoCurrentlyLoggedinUsersPageEndSession.
 */

namespace Drupal\extra_siteinfo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ExtraSiteinfoCurrentlyLoggedinUsersPageEndSession extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'extra_siteinfo_currently_loggedin_users_page_end_session';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $uid = NULL) {
    $form_state->set(['user_id'], $uid);
    return confirm_form($form, 'End Session of Currenlty loggedin Users.', 'admin/reports/extra-siteinfo/currently-loggedin-users', 'Are you sure to end this particular users session?', 'End Session');
  }

  public function submitForm(array &$form_id, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::entityManager()->getStorage('user')->load($form_state->get(['user_id']));
    db_delete('sessions')
      ->condition('uid', $user->uid)
      ->execute();
    drupal_set_message(t('@username ( @userid ) user session has been ended.', [
      '@username' => $user->name,
      '@userid' => $user->uid,
    ]));
    drupal_goto('admin/reports/extra-siteinfo/currently-loggedin-users');
  }

}
