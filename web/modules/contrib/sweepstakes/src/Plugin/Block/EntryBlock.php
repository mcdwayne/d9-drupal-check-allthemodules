<?php

namespace Drupal\Sweepstakes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a sweepstake entry form block.
 *
 * @Block(
 *   id = "sweepstakes_entry_block",
 *   admin_label = @Translation("Sweepstakes Entry Block"),
 *   category = @Translation("Sweepstakes")
 * )
 */
class EntryBlock extends BlockBase implements FormInterface {

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
      'share' => array(
        '#type' => 'checkbox',
        '#default_value' => TRUE,
        '#title' => t('I have read, understand and agree to the above contest rules'),
      ),
      'signup' => array(
        '#type' => 'checkbox',
        '#default_value' => TRUE,
        '#title' => t('Check here if you would like us to e-mail you about contests like this in the future'),
      ),
      'submit' => array(
        '#type' => 'submit',
        '#value' => t('Enter Contest'),
        '#attributes' => array(
          'class' => array('btn large green'),
        ),
      ),
    );
  }

  /**
   * Updates the options of a select list.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The updated form element.
   */
  public function updateOptions(array $form, FormStateInterface $form_state) {
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

  function _sweepstakes_check_unique_entry($nid, $uid) {
    $entries = db_select('sweepstakes_entries')
      ->fields(NULL, array('seid'))
      ->condition('nid', $nid)
      ->condition('uid', $uid)
      ->execute()
      ->rowCount();

    if ($entries > 0) {
      drupal_set_message(t('You are already enrolled in this sweepstakes'), 'error');
      return FALSE;
    }
    return TRUE;
  }

  function _sweepstakes_check_auth_user($uid) {
    if ($uid == 0) {
      drupal_set_message(t("If you do not yet have a MMORPG.COM account, just !link to get one for free!", array('!link' => \Drupal::l('click here', \Drupal\Core\Url::fromRoute('user.register')))), 'error');
      return FALSE;
    }
    return TRUE;
  }

  function _sweepstakes_add_entry($nid, $uid = NULL, $ip_address = NULL, $timestamp = NULL, $source = NULL) {
    if (is_null($uid)) {
      $user = \Drupal::currentUser();
      $uid = $user->uid;
    }

    if (is_null($ip_address)) {
      $ip_address = ip2long(\Drupal::request()->getClientIp());
    }

    if (is_null($timestamp)) {
      $timestamp = time();
    }

    db_insert('sweepstakes_entries')
      ->fields(array(
        'seid' => 0,
        'nid' => $nid,
        'uid' => $uid,
        'ip_address' => $ip_address,
        'timestamp' => $timestamp,
        'source' => $source,
      ))
      ->execute();

    drupal_set_message(t('You have successfully entered the sweepstakes'));
  }

}
