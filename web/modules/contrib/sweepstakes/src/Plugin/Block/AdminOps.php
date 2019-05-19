<?php

namespace Drupal\Sweepstakes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a sweepstake admin ops block.
 *
 * @Block(
 *   id = "sweepstakes_admin_ops_block",
 *   admin_label = @Translation("Sweepstakes Admin Ops Block"),
 *   category = @Translation("Sweepstakes")
 * )
 */
class AdminOps extends BlockBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sweepstakes_entry_block';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return array(
      'sid' => array(
        '#type' => 'hidden',
        '#value' => arg(1),
      ),
      'delete_duplicates' => array(
        '#type' => 'submit',
        '#value' => t('Delete Duplicates'),
        '#submit' => array(
          'sweepstakes_delete_duplicates',
        ),
      ),
      'pick_winners' => array(
        '#type' => 'submit',
        '#value' => t('Pick Winners'),
        '#submit' => array(
          'sweepstakes_pick_winners',
        ),
      ),
      'email_winners' => array(
        '#type' => 'submit',
        '#value' => t('Email Winners'),
        '#submit' => array(
          'sweepstakes_email_winners',
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    if (_sweepstakes_check_unique_entry($form_state['values']['sid'], $user->uid)
      and _sweepstakes_check_auth_user($user->uid)
    ) {
      _sweepstakes_add_entry($form_state['values']['sid'], $user->uid, NULL, NULL, 'user initiated');
    }

  }

  function sweepstakes_delete_duplicates($form, &$form_state) {
    $sweepstake = $form_state['values']['sid'];
    $entries = db_select('sweepstakes_entries')
      ->fields(NULL, array('seid', 'ip_address'))
      ->condition('nid', $sweepstake)
      ->condition('valid', 1)
      ->condition('source', 'user initiated')
      ->execute()
      ->fetchAllKeyed();

    $operations = $repeat_ips = array();
    foreach ($entries as $id => $ip_address) {
      $repeat_ips[$ip_address][] = $id;
      //--
      $operations[] = array(
        'sweepstakes_delete_one_duplicate_check_proxy',
        array($id, $ip_address)
      );
    }

    foreach ($repeat_ips as $ip => $ids) {
      if (count($ids) > 1) {
        $operations[] = array(
          'sweepstakes_delete_one_duplicate',
          array($ids)
        );
      }
    }

    batch_set(array(
      'title' => t('Deleting Duplicates'),
      'operations' => $operations,
      'finished' => 'sweepstakes_delete_duplicates_finished',
    ));
  }

  function sweepstakes_delete_one_duplicate_check_proxy($id, $ip_address, &$context) {
    if (ip2proxy_is_proxy($ip_address)) {
      sweepstakes_delete_one_duplicate($id);
      $context['results'][] = $id;
    }
  }

  function sweepstakes_delete_one_duplicate($id) {
    db_update('sweepstakes_entries')
      ->fields(array('valid' => 0,))
      ->condition('seid', $id)
      ->execute();
  }

  function sweepstakes_delete_duplicates_finished($success, $results, $operations) {
    drupal_set_message($success ?
      \Drupal::translation()->formatPlural(count($results), 'One entry deleted', '@count entries deleted') :
      t('Finished with an error')
    );
  }

  function sweepstakes_pick_winners($form, &$form_state) {
    drupal_goto('node/' . $form_state['values']['sid'] . '/entries/pick-winners');
    exit;
  }

  function sweepstakes_pick_winner_confirmation() {
    return array(
      'sid' => array(
        '#type' => 'hidden',
        '#value' => arg(1),
      ),
      'redraw_interval' => array(
        '#type' => 'textfield',
        '#default_value' => t('24'),
        '#title' => t('Redraw in another'),
        '#field_suffix' => 'hours',
      ),
      'pick_winners' => array(
        '#type' => 'submit',
        '#value' => t('Pick Winners'),
        '#submit' => array(
          'sweepstakes_pick_winners_and_set_redraw',
        ),
      ),
    );
  }

  function sweepstakes_pick_winners_and_set_redraw($form, &$form_state) {

    $form_state['redirect'] = 'node/' . $form_state['values']['sid'] . '/entries';
    $sweepstake = \Drupal::entityManager()->getStorage('node')->load($form_state['values']['sid']);

    //set an auto-redraw for 24 hours later.
    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/sweepstakes.settings.yml and config/schema/sweepstakes.schema.yml.
    \Drupal::configFactory()->getEditable('sweepstakes.settings')->set('sweepstakes_redraw_timings', \Drupal::config('sweepstakes.settings')->get('sweepstakes_redraw_timings')
      + array($sweepstake->nid => REQUEST_TIME + $form_state['values']['redraw_interval'] * 60 * 60))->save();

    $prizes = \Drupal::entityManager()->getStorage('field_collection_item');

    $operations = array();
    foreach ($prizes as $id => $prize) {
      // avoid giving out more than available prizes.
      $prizes_awarded = db_select('sweepstakes_entries')
        ->fields(NULL, array('seid'))
        ->condition('nid', $sweepstake->nid)
        ->condition('prize_id', $id)
        ->execute()
        ->rowCount();

      for ($count = $prizes_awarded; $count < $prize->field_prize_count[\Drupal\Core\Language\Language::LANGCODE_NOT_SPECIFIED][0]['value']; $count++) {
        if (empty($prize->field_prize_giveaway)) {
          // this is a non giveaway prize.
          $operations[] = array(
            'sweepstakes_pick_one_winner_for_non_giveaway',
            array($id, $sweepstake->nid)
          );
        }
        else {
          // this is a giveaway prize.
          $operations[] = array(
            'sweepstakes_pick_one_winner_for_giveaway',
            array(
              $id,
              $sweepstake->nid,
              $prize->field_prize_giveaway[\Drupal\Core\Language\Language::LANGCODE_NOT_SPECIFIED][0]['target_id']
            )
          );
        }
      }
    }

    batch_set(array(
      'title' => t('Picking Winners'),
      'operations' => $operations,
      'finished' => 'sweepstakes_pick_winners_finished',
    ));
  }

  function sweepstakes_pick_one_winner_for_non_giveaway($pid, $sid, &$context) {
    $seid = db_select('sweepstakes_entries')
      ->fields(NULL, array('seid'))
      ->condition('nid', $sid)
      ->condition('prize_id', 0)
      ->condition('valid', 1)
      ->range(0, 1)
      ->orderRandom()
      ->execute()
      ->fetchAssoc();

    if ($seid) {
      db_update('sweepstakes_entries')
        ->fields(array('prize_id' => $pid))
        ->condition('seid', $seid)
        ->execute();
      $context['results'][] = $sid;
    }
  }

  function sweepstakes_pick_one_winner_for_giveaway($pid, $sid, $gid, &$context) {
    $entry = db_select('sweepstakes_entries')
      ->fields(NULL, array('seid', 'uid'))
      ->condition('nid', $sid)
      ->condition('prize_id', 0)
      ->condition('valid', 1)
      ->range(0, 1)
      ->orderRandom()
      ->execute()
      ->fetchAssoc();

    if (is_array($entry)) {
      db_update('sweepstakes_entries')
        ->fields(array('prize_id' => $pid))
        ->condition('seid', $entry['seid'])
        ->execute();

      _giveaway_dispense_key_to_user($gid, $entry['uid']);

      $context['results'][] = $sid;
    }
  }

  function sweepstakes_pick_winners_finished($success, $results, $operations) {
    drupal_set_message($success ?
      \Drupal::translation()->formatPlural(count($results), 'One prize awarded.', '@count prizes awarded.') :
      t('Finished with an error.')
    );
  }

  function sweepstakes_email_winners($form, &$form_state) {
    $sweepstake = \Drupal::entityManager()->getStorage('node')->load($form_state['values']['sid']);
    $prizes = \Drupal::entityManager()->getStorage('field_collection_item');
    $details = db_select('sweepstakes_entries')
      ->fields(NULL, array('uid', 'prize_id'))
      ->condition('nid', $sweepstake->nid)
      ->condition('valid', 1)
      ->condition('prize_id', 0, '>')
      ->condition('confirmed', 0)
      ->execute()
      ->fetchAllKeyed();

    $users = \Drupal::entityManager()->getStorage('user')->loadMultiple(array_keys($details));
    $operations = array();
    foreach ($users as $user) {
      $operations[] = array(
        'sweepstakes_email_one_winner',
        array(
          $user,
          $sweepstake->nid,
          $sweepstake->field_sweepstakes_winner_message[\Drupal\Core\Language\Language::LANGCODE_NOT_SPECIFIED][0]['value'],
          $prizes[$details[$user->uid]]
        )
      );
    }

    batch_set(array(
      'title' => t('Emailing Winners'),
      'operations' => $operations,
      'finished' => 'sweepstakes_email_winners_finished',
    ));
  }

  function sweepstakes_email_one_winner($account, $sid, $message, $prize, &$context) {
    // @FIXME
    // url() expects a route name or an external URI.
    // drupal_mail('sweepstakes', 'sweepstakes_winner', $account->mail, user_preferred_language($account), array(
    //     'body' => $message,
    //     'subject' => 'Congratulations on winning the sweepstakes',
    //     'headers' => 'simple',
    //     'prize' => $prize,
    //     'user' => $account,
    //     'link' => url('node/' . $sid, array('absolute' => TRUE,)),
    //   ));

    $context['results'][] = $account->uid;
  }

  function sweepstakes_mail($key, &$message, $params) {
    switch ($key) {
      case 'sweepstakes_winner':
        $search = array('#username#', '#prize#', '#link#');
        $replace = array(
          $params['user']->name,
          $params['prize']->field_prize_description[\Drupal\Core\Language\Language::LANGCODE_NOT_SPECIFIED][0]['safe_value'],
          $params['link'],
        );
        $message['subject'] = t($params['subject']);
        $message['body'][] = str_replace($search, $replace, $params['body']);
        $message['headers']['Content-Type'] = 'text/html; charset=UTF-8; format=flowed';
        break;
    }
  }

  function sweepstakes_email_winners_finished($success, $results, $operations) {
    drupal_set_message($success ?
      \Drupal::translation()->formatPlural(count($results), 'One email sent.', '@count emails sent.') :
      t('Finished with an error.')
    );
  }

}
