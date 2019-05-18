<?php

namespace Drupal\balance_tracker\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Builds the date selection form at the top of the balance page.
 */
class BalanceTrackerDateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'balance_tracker_date_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    if ($user === NULL) {
      $user = User::load($this->currentUser()->id());
    }

    $output = '';

    // Preset $to and $from based on form variables if available, or on sensible
    // defaults if not. 86400 added to $to since to set the time to the end of the
    // selected day.
    if ($form_state->get('date_to')) {
      $to = strtotime($form_state->get('date_to')) + 86400;
    }
    else {
      $to = REQUEST_TIME;
    }

    // Use value from form.
    if ($form_state->get('date_from')) {
      $from = strtotime($form_state->get('date_from'));
    }
    else {
      $from = $user->getCreatedTime();
    }
    if ($user->id() !== $this->currentUser()->id()) {
      // Looking at another user's account.
      $output .= '<p>' . $this->t("This is @user's balance sheet.", ['@user' => $user->getDisplayName()]) . '</p>';
    }
    else {
      // Looking at own account.
      $output .= '<p>' . $this->t('This is your balance sheet.') . '</p>';
    }

    $output .= '<p>' . $this->t("This shows recent credits and debits to your account. Entries from a specific date period may be viewed by selecting a date range using the boxes below labelled 'From' and 'To'") . '</p>';

    $form['helptext'] = ['#markup' => $output];

    $form_state->disableRedirect();

    $format = 'm/d/Y';

    $form['date_from'] = [
      '#type' => 'date',
      '#title' => t('From'),
      '#default_value' => date($format, $from),
      '#date_format' => $format,
      '#date_label_position' => 'within',
      '#date_increment' => 15,
      '#date_year_range' => '-3:+3',
    ];
    $form['date_to'] = [
      '#type' => 'date',
      '#title' => t('To'),
      '#default_value' => date($format, $to),
      '#date_format' => $format,
      '#date_label_position' => 'within',
      '#date_increment' => 15,
      '#date_year_range' => '-3:+3',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Display'),
    ];
    $form['accounts'] = [
      '#type' => 'balance_table',
      '#date_from' => $from,
      '#date_to' => $to,
      '#user' => $user,
    ];
    $form['pager'] = [
      '#type' => 'pager',
      '#tags' => NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strtotime($form_state->getValue('date_from')) === FALSE) {
      $form_state->setErrorByName('date_from', $this->t('From date was not in a recognizable date format. <em>mm/dd/YYYY</em> should be recognized.'));
    }
    else {
      $form_state->set('date_from', $form_state->getValue('date_from'));
    }

    if (strtotime($form_state->getValue('date_to')) === FALSE) {
      $form_state->setErrorByName('date_to', $this->t('To date was not in a recognizable date format. <em>mm/dd/YYYY</em> should be recognized.'));
    }
    else {
      $form_state->set('date_to', $form_state->getValue('date_to'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Make the form rebuild with the new from and to dates.
    $form_state->setRebuild(TRUE);
  }

}
