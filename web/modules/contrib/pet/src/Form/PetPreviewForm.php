<?php

namespace Drupal\pet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pet\PetInterface;
use Drupal\user\Entity\User;

class PetPreviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pet_preview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PetInterface $pet = NULL) {
    $body_description = t('Review and edit standard template before previewing. This will not change the template for future emailings, just for this one. To change the template permanently, go to the template page. You may use the tokens below.');
    $storage = $form_state->getStorage();
    if (pet_isset_or($storage['step']) == 3) {
      drupal_set_message(t('Email(s) sent'));
      $form_state->setStorage([]);
    }

    $step = empty($storage['step']) ? 1 : $storage['step'];
    $storage['step'] = $step;
    $storage['pet'] = $pet;

    // Get any query args
    $nid = $storage['nid'] = pet_is_natural(pet_isset_or($_REQUEST['nid'])) ? $_REQUEST['nid'] : NULL;
    $uid = $storage['uid'] = pet_is_natural(pet_isset_or($_REQUEST['uid'])) ? $_REQUEST['uid'] : NULL;
    $recipient_callback = $storage['recipient_callback'] = (
      pet_isset_or($_REQUEST['recipient_callback']) === 'true' ||
      pet_isset_or($_REQUEST['uid']) === '0' // backward compatibility
    );

    switch ($step) {
      case 1:
        if ($recipient_callback) {
          $default_mail = t('Recipient list will be generated for preview.');
        }
        elseif (pet_isset_or($storage['recipients_raw'])) {
          $default_mail = $storage['recipients_raw'];
        }
        else {
          $default_mail = '';
          if ($uid) {
            if ($account = User::load($uid)) {
              $default_mail = $account->getEmail();
            }
            else {
              drupal_set_message(t('Cannot load a user with uid @uid.', ['@uid' => $uid]), 'error');
            }
          }
        }
        $form['recipients'] = [
          '#title' => t('To'),
          '#type' => 'email',
          '#required' => TRUE,
          '#default_value' => $default_mail,
          '#description' => t('Enter the recipient(s) separated by lines or commas. A separate email will be sent to each, with token substitution if the email corresponds to a site user.'),
          '#disabled' => $recipient_callback,
        ];
        $form['copies'] = [
          '#title' => t('Copies'),
          '#type' => 'details',
          '#collapsed' => empty($pet->getCCDefault()) && empty($pet->getBCCDefault()),
        ];
        $form['copies']['cc'] = [
          '#title' => t('Cc'),
          '#type' => 'email',
          '#rows' => 3,
          '#default_value' => pet_isset_or($storage['cc']) ? $storage['cc'] : $pet->getCCDefault(),
          '#description' => t('Enter any copied emails separated by lines or commas.'),
        ];
        $form['copies']['bcc'] = [
          '#title' => t('Bcc'),
          '#type' => 'email',
          '#rows' => 3,
          '#default_value' => pet_isset_or($storage['bcc']) ? $storage['bcc'] : $pet->getBCCDefault(),
          '#description' => t('Enter any blind copied emails separated by lines or commas.'),
        ];
        $form['subject'] = [
          '#type' => 'textfield',
          '#title' => t('Subject'),
          '#maxlength' => 255,
          '#default_value' => isset($storage['subject']) ? $storage['subject'] : $pet->getSubject(),
          '#required' => TRUE,
        ];
        if (!(pet_has_mimemail() && $pet->getSendPlain())) {
          $form['mail_body'] = [
            '#type' => 'text_format',
            '#title' => t('Body'),
            '#default_value' => pet_isset_or($storage['mail_body']) ? $storage['mail_body'] : $pet->getMailbody(),
            '#rows' => 15,
            '#description' => $body_description,
          ];
        }
        if (pet_has_mimemail()) {
          $form['mimemail'] = [
            '#type' => 'details',
            '#title' => t('Plain text body'),
            '#collapsible' => TRUE,
            '#collapsed' => !(pet_has_mimemail() && $pet->send_plain),
          ];
          $form['mimemail']['mail_body_plain'] = [
            '#type' => 'textarea',
            '#title' => t('Plain text body'),
            '#default_value' => isset($storage['mail_body_plain']) ?
              $storage['mail_body_plain'] :
              $pet->getMailbodyPlain(),
            '#rows' => 15,
            '#description' => $body_description,
          ];
        }
        $form['tokens'] = pet_token_help();
        $form['preview'] = [
          '#type' => 'submit',
          '#value' => t('Preview'),
        ];
        break;

      case 2:
        $values = $form_state->getValues();
        $form['info'] = [
          '#value' => t('A preview of the email is shown below. If you\'re satisfied, click Send. If not, click Back to edit the email.'),
        ];
        $form['recipients'] = [
          '#title' => t('To'),
          '#type' => 'hidden',
          '#required' => TRUE,
          '#default_value' => $storage['recipients'],
          '#description' => t('Enter the recipient(s) separated by lines or commas. A separate email will be sent to each, with token substitution if the email corresponds to a site user.'),
          '#disabled' => $recipient_callback,
        ];
        $form['recipients_display'] = [
          '#type' => 'textarea',
          '#title' => t('To'),
          '#rows' => 4,
          '#value' => pet_recipients_formatted($storage['recipients']),
          #disabled' => TRUE,
        ];
        if ($values['cc']) {
          $form['cc'] = [
            '#type' => 'textarea',
            '#title' => t('CC'),
            '#rows' => 4,
            '#value' => $values['cc'],
            '#disabled' => TRUE,
          ];
        }
        if ($values['bcc']) {
          $form['bcc'] = [
            '#type' => 'textarea',
            '#title' => t('BCC'),
            '#rows' => 4,
            '#value' => $values['bcc'],
            '#disabled' => TRUE,
          ];
        }
        $form['subject'] = [
          '#type' => 'textfield',
          '#title' => t('Subject'),
          '#size' => 80,
          '#value' => $storage['subject_preview'],
          '#disabled' => TRUE,
        ];
        if (!pet_has_mimemail() || !$pet->getSendPlain()) {
          $form['body_label'] = [
            '#prefix' => '<div class="pet_body_label">',
            '#suffix' => '</div>',
            '#markup' => '<label>' . t('Body as HTML') . '</label>',
          ];
          $form['body_preview'] = [
            '#prefix' => '<div class="pet_body_preview">',
            '#suffix' => '</div>',
            '#markup' => $storage['body_preview'],
          ];
          $form['mail_body'] = [
            '#type' => 'textarea',
            '#title' => t('Body'),
            '#rows' => 15,
            '#value' => $storage['body_preview'],
            '#disabled' => TRUE,
          ];
        }
        $plain_text = trim($storage['body_preview_plain']);
        if (pet_has_mimemail() && ($pet->getSendPlain() || !empty($plain_text))) {
          $form['mail_body_plain'] = [
            '#type' => 'textarea',
            '#title' => t('Plain text body'),
            '#rows' => 15,
            '#value' => $storage['body_preview_plain'],
            '#disabled' => TRUE,
          ];
        }
        $form['back'] = [
          '#type' => 'submit',
          '#value' => t('Back'),
          '#submit' => ['::stepBack'],
        ];
        $form['submit'] = [
          '#type' => 'submit',
          '#value' => t('Send email(s)'),
        ];
        break;
    }

    $form_state->setStorage($storage);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $recipients_raw = $form_state->getValue('recipients');
    $recipients = [];
    $errors = $this->pet_validate_recipients($form_state, $recipients);

    if (!empty($errors)) {
      foreach ($errors as $error) {
        $form_state->setErrorByName('recipients', $error);
      }
    }
    else {
      $form_state->setValue('recipients', $recipients);
      $form_state->setValue('recipients_raw', $recipients_raw);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $step = empty($storage['step']) ? 1 : $storage['step'];
    $storage['step'] = $step;
    $values = $form_state->getValues();

    switch ($step) {
      case 1:
        $form_state->setRebuild(TRUE);
        $storage['recipients_raw'] = $values['recipients'];
        $storage['recipients'] = $values['recipients'];
        $storage['subject'] = $values['subject'];
        $storage['mail_body'] = pet_isset_or($values['mail_body']);
        $storage['mail_body_plain'] = pet_isset_or($values['mail_body_plain']);
        $storage['cc'] = $values['cc'];
        $storage['bcc'] = $values['bcc'];
        $form_state->setStorage($storage);
        $this->pet_make_preview($form_state);
        $storage = $form_state->getStorage();
        break;

      case 2:
        $form_state->setRebuild(TRUE);
        $pet = $storage['pet'];
        $recipients = $storage['recipients'];
        $options = [
          'nid' => $storage['nid'],
          'subject' => $storage['subject'],
          'body' => $storage['mail_body'],
          'body_plain' => $storage['mail_body_plain'],
          'from' => NULL,
          'cc' => $storage['cc'],
          'bcc' => $storage['bcc'],
        ];
        pet_send_mail($pet->id(), $recipients, $options);
        break;
    }

    $storage['step']++;
    $form_state->setStorage($storage);
  }

  /**
   * Generate a preview of the tokenized email for the first in the list.
   */
  public function pet_make_preview(FormStateInterface &$form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();
    $params = [
      'pet_uid' => is_array($storage['recipients']) ? $storage['recipients'][0]['uid'] : NULL,
      'pet_nid' => $storage['nid'],
    ];
    $subs = pet_substitutions($storage['pet'], $params);

    $token = \Drupal::token();
    $storage['subject_preview'] = $token->replace($values['subject'], $subs);
    $storage['body_preview'] = $token->replace(pet_isset_or($values['mail_body']), $subs);
    $storage['body_preview_plain'] = $token->replace(pet_isset_or($values['mail_body_plain']), $subs);
    $form_state->setStorage($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function stepBack(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $form_state->set('step', 1);
  }

  /**
   * Validate existence of a non-empty recipient list free of email errors.
   */
  function pet_validate_recipients(FormStateInterface $form_state, &$recipients) {
    $errors = [];
    $recipients = [];

    if ($form_state->getValue('recipient_callback')) {
      // Get recipients from callback
      $mails = pet_callback_recipients($form_state);
      if (!is_array($mails)) {
        $errors[] = t('There is no recipient callback defined for this template or it is not returning an array.');
        return $errors;
      }
    }
    else {
      // Get recipients from form field
      $mails = pet_parse_mails($form_state->getValue('recipients'));
    }

    // Validate and build recipient array with uid on the fly
    foreach ($mails as $mail) {
      if (!\Drupal::service('email.validator')->isValid($mail)) {
        $errors[] = t('Invalid email address found: %mail.', ['%mail' => $mail]);
      }
      else {
        $recipients[] = ['mail' => $mail, 'uid' => pet_lookup_uid($mail)];
      }
    }

    // Check for no recipients
    if (empty($errors) && count($recipients) < 1) {
      $errors[] = t('There are no recipients for this email.');
    }

    return $errors;
  }

  /**
   * Return an array of email recipients provided by a callback function.
   */
  function pet_callback_recipients(FormStateInterface $form_state) {
    $callback = $form_state->getValue('recipient_callback');

    if (!empty($callback)) {
      if (function_exists($callback)) {
        $recipients = $callback();

        // Remove uid for backward compatibility
        if (isset($recipients) && is_array($recipients)) {
          $new_recipients = [];
          foreach ($recipients as $recipient) {
            $recipient = preg_replace('/^[0-9]*\|/', '', $recipient);
            $new_recipients[] = $recipient;
          }
          return $new_recipients;
        }
      }
    }
    return NULL;
  }
}
