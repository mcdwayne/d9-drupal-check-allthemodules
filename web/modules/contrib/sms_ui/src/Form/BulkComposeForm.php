<?php

namespace Drupal\sms_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Exception\RecipientRouteException;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms_ui\Entity\SmsHistory;
use Drupal\sms_ui\Utility\PhoneNumberFormatHelper;

/**
 * Form for composing bulk SMS.
 */
class BulkComposeForm extends FormBase {

  use ComposeFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_ui_bulk_compose_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->getBaseComposeForm($form, $form_state);
    $form['recipients_container']['recipients'] = [
      '#type' => 'textarea',
      '#title' => t('Recipients'),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Recipients - use commas or spaces to separate multiple phone numbers'),
      '#rows' => 3,
      '#resizable' => FALSE,
      '#required' => TRUE,
      '#attributes' => ['class' => ['text-common']],
    ];
    // Update default value if message was 'cloned'.
    if ($this->getRequest()->query->has('_stored')) {
      $history = SmsHistory::load($this->getRequest()->query->get('_stored'));
      $form['recipients_container']['recipients']['#default_value'] = implode("\n", $history->getRecipients());
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Clean and format each number.
    $numbers = [];
    $skipped_numbers = [];
    foreach (PhoneNumberFormatHelper::splitNumbers($form_state->getValue('recipients')) as $number) {
      if ($formatted = PhoneNumberFormatHelper::formatNumber($number, $form_state->getValue('country_code'))) {
        $numbers[] = $formatted;
      }
      else {
        $skipped_numbers[] = $number;
      }
    }
    $form_state->setValue('recipients_array', $numbers);
    $form_state->set('skipped_numbers', $skipped_numbers);

    if (empty($numbers)) {
      $form_state->setErrorByName('recipients', $this->t('Please specify at least one valid number'));
    }

    // Validate sender ID.
    $this->validateSenderId($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Call the submit handler for the trait.
    $this->traitSubmitHandler($form, $form_state);
    if ($form_state->getTriggeringElement()['#id'] === 'edit-send-sms') {
      if ($this->currentUser()->hasPermission('send sms')) {
        // Create options object from form values by removing unnecessary form
        // variables.
        $options = $form_state->cleanValues()->getValues();
        // Create SMS object and send through the SMS Provider service.
        $sms = new SmsMessage();
        $sms
          ->setSenderNumber($options['sender'])
          ->addRecipients($options['recipients_array'])
          ->setMessage($options['message'])
          ->setUid($this->currentUser()->id());

        // Unset form variables that have been utilized already.
        unset($options['recipients'], $options['recipients_array'], $options['sender'], $options['message']);
        foreach ($options as $name => $option) {
          $sms->setOption($name, $option);
        }
        try {
          // Process the SMS as specified by the user.
          $processed_sms = $this->dispatchMessage($sms, $form_state->getValue('send_direct'));

          // Get the history item for the messages and pass that value to the
          // SendComplete form.
          $history = \Drupal::service('sms_ui.history_subscriber')->getHistoryEntity();

          // Successful message should redirect to the done form.
          $form_state->setRedirect('sms_ui.send_status', ['sms_history' => $history->id()], ['query' => $this->getDestinationArray()]);

          // Set message for skipped numbers.
          if ($count = count($form_state->get('skipped_numbers'))) {
            drupal_set_message($this->t('@count recipient numbers were skipped because they are invalid',
              ['@count' => $count]), 'warning');
          }
        }
        catch (RecipientRouteException $e) {
          // Invalid gateway configuration.
          drupal_set_message('There was an error in the SMS gateway configuration. Please contact the administrator.', 'error');
          $this->logger('sms_ui')->error($e->getMessage());
        }
      }
      else {
        drupal_set_message($this->t('You are not permitted to send SMS messages. Please contact the administrator.'), 'error');
      }
    }
  }

}
