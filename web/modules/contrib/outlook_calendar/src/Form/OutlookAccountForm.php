<?php

namespace Drupal\outlook_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Provides the form for adding outlook account.
 */
class OutlookAccountForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'outlook_account_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $conn = Database::getConnection();
    $record = [];
    if (isset($_GET['num'])) {
      $query = $conn->select('outlook_calendar', 'oe')
        ->condition('id', $_GET['num'])->fields('oe');
      $record = $query->execute()
        ->fetchAssoc();
    }
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Exchange Account MailID'),
      '#required' => TRUE,
      '#default_value' => (isset($record['mail']) && $_GET['num']) ? $record['mail'] : '',
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Exchange MailID Password'),
      '#required' => TRUE,
      '#default_value' => (isset($record['password']) && $_GET['num']) ? $record['password'] : '',
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#default_value' => (isset($_GET['num'])) ? $this->t('Update Account') : $this->t('Add Account') ,
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array & $form, FormStateInterface $form_state) {
    $mail = $form_state->getValue('mail');
    $conn = Database::getConnection();
    $query = $conn->select('outlook_calendar', 'oe');
    $query->fields('oe', ['mail']);
    $query->condition('mail', $mail, '=');
    $result = $query->execute()
      ->fetchAll();
    try {
      $result = $query->execute()
        ->fetchAll();
    }
    catch (exception $e) {
      return $e->getMessage();
    }
    if (!empty($result) && empty($_GET['num'])) {
      $form_state->setErrorByName('mail', $this->t('This outlook account id already exists. Please enter a unique outlook account id'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array & $form, FormStateInterface $form_state) {
    $conn = Database::getConnection();
    $account = $this->currentUser();
    $uid = $account->id();
    $field = $form_state->getValues();
    $re_url = Url::fromRoute('outlook_calendar.account');
    $mail = $field['mail'];
    $password = $field['password'];
    if (isset($_GET['num'])) {
      $field = [
        'mail' => $mail,
        'password' => $password,
      ];
      $conn->update('outlook_calendar')
        ->fields($field)->condition('id', $_GET['num'])->execute();
      drupal_set_message($this->t('The Outlook Account has been succesfully updated'));
      $form_state->setRedirectUrl($re_url);
    }
    else {
      $field = [
        'mail' => $mail,
        'password' => $password,
        'uid' => $uid,
      ];
      $conn->insert('outlook_calendar')
        ->fields($field)->execute();
      drupal_set_message($this->t('The Outlook Account has been succesfully saved'));
      $form_state->setRedirectUrl($re_url);

    }
  }

}
