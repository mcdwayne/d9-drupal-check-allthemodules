<?php

namespace Drupal\mailman_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Mailman integration user subcribe form.
 */
class MailmanIntegrationUserForm extends FormBase {

  /**
   * The account the Mailman Integration set is for.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailman_integration_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $this->user = $user;

    $form = [];
    $form['mailman_integration']['mail_lists'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mailing Lists'),
      '#description' => $this->t('Subscribe or unsubscribe to a mailing list.'),
      '#collapsible' => FALSE,
      '#attributes' => [
        'class' => ['mailman_userlist_fieldset'],
      ],
    ];
    $mail_lists = \Drupal::service('mailman_integration.mailman_controler')->userSubscribeList($user->id());
    if (empty($mail_lists) || !$user->id()) {
      $form['mailman_integration']['mail_lists']['empty_list'] = [
        '#type' => 'markup',
        '#prefix' => '<p>',
        '#markup' => $this->t('No mailing lists are available.'),
        '#suffix' => '</p>',
      ];
      return $form;
    }
    foreach ($mail_lists as $list) {
      $list_id = $list->list_id;
      $description = mailman_integration_match_desc($list->description);
      $desc = $description['description'];
      $form['mailman_integration']['mail_lists']['list-' . $list_id] = [
        '#type' => 'fieldset',
        '#title' => SafeMarkup::checkPlain($list->list_name),
        '#description' => $desc,
        '#collapsible' => TRUE,
        '#attributes' => [
          'class' => [
            'mailman_userlist_inner_fieldset',
          ],
        ],
      ];
      $form['mailman_integration']['mail_lists']['list-' . $list_id]['subscribe-' . $list_id] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Subscribe'),
        '#default_value' => ($list->uid) ? 1 : 0,
        '#description' => $this->t('Check for subscribe this mailing list.'),
        '#weight' => 1,
      ];
    }
    if (!empty($mail_lists)) {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ];
      $form['mailman_user_account'] = [
        '#type' => 'value',
        '#value' => $user,
      ];
      $form['mailman_user_old_status'] = [
        '#type' => 'value',
        '#value' => $mail_lists,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $old_subscribe_status = $form_state->getValue(['mailman_user_old_status']);
    $account = $form_state->getValue(['mailman_user_account']);
    foreach ($old_subscribe_status as $value) {
      $subscribe_status = $form_state->getValue(['subscribe-' . $value->list_id]);
      $old_status = ($value->uid) ? $value->uid : '';
      $list_name = $value->list_name;
      $changes = 0;
      if (!$old_status && $subscribe_status) {
        $changes = 1;
        // Subscribe Mailman.
        mailman_integration_subscribe($list_name, $account->getEmail());
        // Update user option.
        mailman_integration_set_user_option($list_name, $account->getEmail(), 'fullname', $account->getAccountName());
        // Insert into mailman user table.
        \Drupal::service('mailman_integration.mailman_controler')->insertUsers($list_name, $account->getEmail(), $value->list_id, $account->id(), $user->id());
      }
      elseif ($old_status && !$subscribe_status) {
        $changes = 1;
        // Unsubscribe Mailman.
        mailman_integration_unsubscribe($list_name, $account->getEmail());
        // Remove from mailman user table.
        \Drupal::service('mailman_integration.mailman_controler')->removeListUsers($list_name, $account->getEmail(), $value->list_id);
      }
      if ($changes) {
        drupal_set_message($this->t('The changes have been saved.'));
      }
    }
  }

}
