<?php

namespace Drupal\mailing\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MailingSettings extends ConfigFormBase{

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames () {
    return ['mailing.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId () {
    return 'mailing_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm (array $form, FormStateInterface $form_state) {
    $message = $this->config('mailing.settings')->get('message');

    $queue = \Drupal::queue('send_message_queue');
    $number_of_items = $queue->numberOfItems();

    if ($number_of_items) {
      $form['info_text'] = [
        '#type' => 'markup',
        '#markup' => t('This queue is already running, there are still: ' . $number_of_items),
      ];

      $form['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel current queue'),
        '#disable' => TRUE,
      ];

    } else {
      $form['message'] = [
        '#type' => 'textarea',
        '#title' => t('Message'),
        '#default_value' => $message,
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Send mail'),
        '#disable' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm (array &$form, FormStateInterface $form_state) {
    $queue = \Drupal::queue('send_message_queue');
    if ($form_state->getTriggeringElement()['#id'] == 'edit-delete'){
      $queue->deleteQueue();
    } else {
      $queue->createQueue();
      $message = $form_state->getValue('message');
      $template = '[mailing:all-active-users]';
      $start = stripos($form_state->getValue('message'), $template);

      if($start){
        $message = str_replace($template, '#user#', $message);
        $user = \Drupal::token()->replace($template);
        $users = explode('#rm#', $user);
      }

      $message = \Drupal::token()->replace($message);
      foreach ($users as $user){
        $query = \Drupal::database()->select('users_field_data', 'u');
        $query->addField('u','mail','m');
        $query->condition('u.name', $user, '=');
        $email = $query->execute()->fetchField();
        if($start){
          $messageFinal = str_replace('#user#', $user, $message);
        }

        $queue->createItem([
          'message' => $messageFinal,
          'toMail' => $email,
          'mail' => \Drupal::config('system.site')->get('mail'),
        ]);
      }
      \Drupal::configFactory()->getEditable('mailing.settings')
      ->set('message', $form_state->getValue('message'))->save();
    }
  }
}