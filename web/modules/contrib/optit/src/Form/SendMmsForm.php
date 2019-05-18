<?php

namespace Drupal\optit\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\optit\Optit\Optit;

/**
 * Defines a form that sends a MMS.
 */
class SendMmsForm extends KeywordMessageSmsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optit_send_mms_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $keyword_id = NULL, $phone = NULL, $interest_id = NULL) {
    $form['media'] = array(
      '#type' => 'file',
      '#title' => t('Media file'),
      '#name' => 'files[media]',
      '#description' => t('Upload a media file to be sent with MMS. Allowed extensions: jpg, jpeg, png, gif, vnd, wap, wbpm, bpm, amr, x-wav, aac, qcp, 3gpp, 3gpp2'),
      '#weight' => 9
    );

    $form += parent::buildForm($form, $form_state, $keyword_id, $phone, $interest_id);

    return $form;
  }


  function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $validators = array(
      'file_validate_extensions' => array('jpg jpeg png gif vnd wap wbpm bpm amr x-wav aac qcp 3gpp 3gpp2'),
    );
    $file = file_save_upload('media', $validators);

    // If file was not upload, skip validation.
    if (is_null($file)) {
      return;
    }

    // Make sure $file is an instance of File class.
    if (is_array($file) && isset($file[0])) {
      $file = $file[0];
    }

    // File extension validation error happened, no need to continue.
    if (!$file) {
      $form_state->setErrorByName('media');
      return;
    }
    // Make sure public folder exists and is writable.
    $dir = 'public://optit';
    if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
      drupal_mkdir($dir);
    }

    // Move the file to the public folder or throw an error.
    if ($file = file_move($file, "{$dir}/{$file->getFilename()}")) {
      $form_state->setValue('media', $file);
    }
    else {
      $form_state->setErrorByName('media', $this->t('Failed to write the uploaded file the file folder.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var File $media */
    $media = $form_state->getValue('media');
    $keyword_id = $form_state->getValue('keyword_id');
    $title = $form_state->getValue('title');
    $message = $form_state->getValue('message');
    $content_url = NULL;
    if ($media) {
      $content_url = file_create_url($media->getFileUri());
    }

    $optit = Optit::create();

    $success = $optit->messageKeywordMMS($keyword_id, $title, $message, $content_url);

    if ($success) {
      drupal_set_message($this->t('Message was successfully sent.'));
    }
    else {
      drupal_set_message($this->t('The message could not be sent. Please consult error log for details.'), 'error');
    }
  }

  /**
   * Adds the message to the bulk messages temp store.
   * @todo: Refactor! Lots of code duplication with KeywordMessageSmsForm!
   */
  function addToBulk(array &$form, FormStateInterface $form_state) {
    $optit = Optit::create();
    $media = $form_state->getValue('media');
    $keyword_id = $form_state->getValue('keyword_id');
    $phones = [];
    // If phone number was not set -- message all subscribers to the keyword.
    if (!$form_state->getValue('phone')) {
      $subscriptions = $optit->subscriptionsGet($keyword_id);
      foreach ($subscriptions as $subscription) {
        $phones[] = $subscription->get('phone');
      }
    }
    // Else iterate through submitted values and make a nice flat array
    else {
      // @todo: Wasn't this supposed to be validation's responsibility?!?!
      foreach ($form_state->getValue('phone') as $phone => $selected) {
        if ($selected) {
          $phones[] = $phone;
        }
      }
    }

    $message = [
      'title' => $form_state->getValue('title'),
      'message' => $form_state->getValue('message'),
      'phones' => $phones,
      'content_url' => NULL
    ];

    if ($media) {
      $message['content_url'] = file_create_url($media->getFileUri());
    }

    /** @var \Drupal\user\PrivateTempStore $tempstore */
    $tempstore = \Drupal::service('user.private_tempstore')->get('optit_bulk');
    $mms_messages = $tempstore->get('mms_messages');
    if (!isset($mms_messages[$keyword_id])) {
      $mms_messages[$keyword_id] = [];
    }
    $mms_messages[$keyword_id][] = $message;
    $tempstore->set('mms_messages', $mms_messages);

    drupal_set_message($this->t('MMS successfully added to the bulk.'));
  }
}
