<?php

namespace Drupal\mailjet_sign_up\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Mailjet\Client;
use Mailjet\Resources;


class SignUpForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'mailjet_sign_up_form';
  }

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $block_config = $this->getBlockConfig($form_state);

    $form['header'] = ['#markup' => '<span class="intro">' . $block_config['header'] . '</span>'];
    $form['mail'] = [
      '#type' => 'textfield',
      '#attributes' => ['placeholder' => $block_config['placeholder']],
      '#default_value' => '',
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('OK'),
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the list ID.
    if ($list_id = $this->getListId($form_state)) {
      $response = $this->subscribe($list_id, $form_state);
      if ($response->success()) {
        drupal_set_message($this->t('You successfully signed up to our newsletter. Thank you!'));
        return;
      }
    }

    drupal_set_message($this->t('Sorry, your sign-up failed.'), 'error');
    if ($response->getReasonPhrase()) {
      drupal_set_message($this->t("Here's the error message from Mailjet subscription manager: %error", ['%error' => $response->getReasonPhrase()]), 'error');
    }
  }

  protected function subscribe($list_id, FormStateInterface $form_state) {
    $data = $this->buildSubscriptionData($list_id, $form_state);
    return $this->getMailjetClient($this->getBlockConfig($form_state))
      ->post(Resources::$ContactslistManagecontact, $data);
  }

  protected function buildSubscriptionData($list_id, FormStateInterface $form_state) {
    return [
      'id' => $list_id,
      'body' => [
        'Email' => $form_state->getValue('mail'),
        'Action' => 'addforce',
      ],
    ];
  }

  protected function getListId(FormStateInterface $form_state) {
    $response = $this->getMailjetClient($this->getBlockConfig($form_state))
      ->get(Resources::$Contactslist);
    if ($response->success()) {
      // We assume there's only one list.
      return $response->getData()[0]['ID'];
    }
  }

  protected function getMailjetClient(array $block_config) {
    return new Client($block_config['api_key'], $block_config['secret_key']);
  }

  protected function getBlockConfig(FormStateInterface $form_state) {
    // @see SignUpBlock::build().
    return $form_state->getBuildInfo()['args'][0];
  }
}
